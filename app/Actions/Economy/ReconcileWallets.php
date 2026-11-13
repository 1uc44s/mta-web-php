<?php

declare(strict_types=1);

namespace App\Actions\Economy;

use App\Enums\AuditAction;
use App\Models\User;
use App\Models\WalletReconciliationRun;
use App\Notifications\PanelNotice;
use App\Services\Audit\AuditLogger;
use App\Services\Integrations\OpsAlerts;
use Illuminate\Support\Facades\DB;

/**
 * Günlük mutabakat — docs/SRS.md FR-WAL-007, DATABASE §6.9. Her cüzdan için `SUM(credit) - SUM(debit) = balance`
 * ve son ledger kaydının `balance_after` değeri = `balance` doğrulanır. Fark varsa kritik audit + `payments.reconcile`
 * yetkililerine panel bildirimi.
 */
final class ReconcileWallets
{
    public function __construct(private readonly AuditLogger $audit, private readonly OpsAlerts $ops) {}

    public function run(?User $actor = null): WalletReconciliationRun
    {
        $run = WalletReconciliationRun::query()->create(['triggered_by' => $actor?->id, 'started_at' => now()]);

        $ledger = DB::table('wallet_transactions')
            ->selectRaw("wallet_id, SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) AS net, MAX(id) AS last_id")
            ->groupBy('wallet_id');

        $rows = DB::table('wallets as w')
            ->leftJoinSub($ledger, 'l', 'l.wallet_id', '=', 'w.id')
            ->leftJoin('wallet_transactions as t', 't.id', '=', 'l.last_id')
            ->get(['w.id', 'w.balance', DB::raw('COALESCE(l.net, 0) AS net'), DB::raw('COALESCE(t.balance_after, 0) AS last_after')]);

        $mismatches = [];
        $totalBalance = 0;
        $ledgerNet = 0;

        foreach ($rows as $row) {
            $balance = (int) $row->balance;
            $net = (int) $row->net;
            $totalBalance += $balance;
            $ledgerNet += $net;

            if ($balance !== $net || $balance !== (int) $row->last_after) {
                $mismatches[] = ['wallet_id' => (int) $row->id, 'balance' => $balance, 'ledger_net' => $net];
            }
        }

        $run->forceFill([
            'wallets_checked' => $rows->count(),
            'mismatches' => count($mismatches),
            'total_balance' => $totalBalance,
            'ledger_net' => $ledgerNet,
            'details' => $mismatches === [] ? null : array_slice($mismatches, 0, 100),
            'finished_at' => now(),
        ])->save();

        if ($mismatches !== []) {
            $this->audit->record(AuditAction::WalletReconciliationMismatch, $run, [
                'after' => ['mismatches' => count($mismatches), 'wallet_ids' => array_slice(array_column($mismatches, 'wallet_id'), 0, 20)],
            ], null, $actor, $actor === null ? 'scheduler' : 'user');
            $this->ops->critical('wallet.reconciliation_mismatch', ['mismatches' => count($mismatches)]);

            User::query()->whereHas('roles.permissions', fn ($q) => $q->where('key', 'payments.reconcile'))->get()
                ->each(fn (User $u) => $u->notify(new PanelNotice('reconciliation_mismatch', __('economy.notice.mismatch', ['count' => count($mismatches)]), route('admin.payments.reconciliation'))));
        }

        return $run;
    }
}
