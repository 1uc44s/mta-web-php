<?php

declare(strict_types=1);

namespace App\Actions\Economy;

use App\Enums\AuditAction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAdjustmentRequest;
use App\Models\WalletTransaction;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsRepository;
use App\Services\Wallet\LedgerException;
use App\Services\Wallet\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manuel düzeltme ve ters kayıt talepleri — docs/SRS.md FR-WAL-006, ROUTES §6.7 (ekonomi), ADMIN §8.
 *   `settings.wallet.four_eyes_threshold` üstü → onaylayan **farklı** biri olmalı.
 *   Eşik altı → talep eden kendi onaylayabilir (`wallet.allow_self_apply_below_threshold`, kapatılabilir).
 * Uygulama yalnızca `LedgerService` üzerinden; yetersiz bakiyede talep `pending` kalır.
 */
final class ManageAdjustments
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly AuditLogger $audit,
        private readonly SettingsRepository $settings,
    ) {}

    /** @param  'credit'|'debit'  $direction */
    public function request(User $actor, Wallet $wallet, string $direction, int $amount, string $reason, ?WalletTransaction $reversalOf = null): WalletAdjustmentRequest
    {
        if ($reversalOf !== null) {
            if ($reversalOf->wallet_id !== $wallet->id || $reversalOf->reason === 'reversal' || $reversalOf->reversal()->exists()) {
                throw ValidationException::withMessages(['reason' => __('economy.errors.not_reversible')]);
            }
            $direction = $reversalOf->direction === 'credit' ? 'debit' : 'credit';
            $amount = $reversalOf->amount;
        }

        $request = WalletAdjustmentRequest::query()->create([
            'wallet_id' => $wallet->id,
            'direction' => $direction,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'pending',
            'requires_second_approval' => $amount > (int) $this->settings->get('wallet.four_eyes_threshold', 500),
            'reversal_of_id' => $reversalOf?->id,
            'requested_by' => $actor->id,
        ]);

        $this->audit->record(AuditAction::WalletAdjustmentRequested, $request, [
            'after' => ['wallet_id' => $wallet->id, 'direction' => $direction, 'amount' => $amount, 'reversal_of' => $reversalOf?->ulid, 'four_eyes' => $request->requires_second_approval],
        ], $reason, $actor);

        return $request;
    }

    public function approve(User $actor, WalletAdjustmentRequest $request, ?string $note): WalletTransaction
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['review_note' => __('economy.errors.not_pending')]);
        }

        $self = $request->requested_by === $actor->id;
        if ($self && ($request->requires_second_approval || ! $this->settings->bool('wallet.allow_self_apply_below_threshold'))) {
            throw ValidationException::withMessages(['review_note' => __('economy.errors.four_eyes')]);
        }

        try {
            $transaction = DB::transaction(function () use ($actor, $request, $note): WalletTransaction {
                $locked = WalletAdjustmentRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
                if ($locked->status !== 'pending') {
                    throw ValidationException::withMessages(['review_note' => __('economy.errors.not_pending')]);
                }

                $transaction = $locked->reversal_of_id !== null
                    ? $this->ledger->reverse(WalletTransaction::query()->findOrFail($locked->reversal_of_id), mb_substr('Düzeltme: '.$locked->reason, 0, 255), $actor, 'wallet_adjustment_request', $locked->id)
                    : $this->ledger->post($locked->wallet_id, $locked->direction, $locked->amount, 'admin_adjustment', 'adj:'.$locked->ulid,
                        mb_substr('Yönetim düzeltmesi: '.$locked->reason, 0, 255), 'wallet_adjustment_request', $locked->id, $actor);

                $locked->forceFill([
                    'status' => 'applied', 'reviewed_by' => $actor->id, 'reviewed_at' => now(),
                    'review_note' => $note, 'wallet_transaction_id' => $transaction->id,
                ])->save();

                return $transaction;
            });
        } catch (LedgerException $e) {
            throw ValidationException::withMessages(['review_note' => __('economy.errors.ledger', ['message' => $e->getMessage()])]);
        }

        $reason = $note !== null && trim($note) !== '' ? $note : $request->reason;
        $this->audit->record(AuditAction::WalletAdjustmentApproved, $request, ['after' => ['status' => 'approved']], $reason, $actor);
        $this->audit->record($request->reversal_of_id !== null ? AuditAction::WalletReversalApplied : AuditAction::WalletAdjustmentApplied, $transaction, [
            'after' => ['wallet_id' => $transaction->wallet_id, 'direction' => $transaction->direction, 'amount' => $transaction->amount, 'balance_after' => $transaction->balance_after],
        ], $reason, $actor);

        return $transaction;
    }

    public function reject(User $actor, WalletAdjustmentRequest $request, string $note): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['review_note' => __('economy.errors.not_pending')]);
        }

        $request->forceFill(['status' => 'rejected', 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_note' => $note])->save();
        $this->audit->record(AuditAction::WalletAdjustmentRejected, $request, ['after' => ['status' => 'rejected']], $note, $actor);
    }
}
