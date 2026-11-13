<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\AuditAction;
use App\Models\PaymentOrder;
use App\Models\Refund;
use App\Models\User;
use App\Notifications\PanelNotice;
use App\Services\Audit\AuditLogger;
use App\Services\Payments\PaymentManager;
use App\Services\Wallet\LedgerException;
use App\Services\Wallet\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * İadeler — docs/SRS.md FR-PAY-005 (dört göz). Onaylayan ≠ talep eden. Onayda önce cüzdanda iade edilecek kredi
 * kontrol edilir: yetmiyorsa sağlayıcı çağrılmaz, iade `requires_review` olur (negatif bakiyeye izin verilmez).
 * Sağlayıcı başarılıysa ledger debit (`refund:{ulid}`) ve sipariş `refunded|partially_refunded`.
 */
final class ManageRefunds
{
    public function __construct(
        private readonly PaymentManager $providers,
        private readonly LedgerService $ledger,
        private readonly AuditLogger $audit,
    ) {}

    public function request(User $actor, PaymentOrder $order, int $amountMinor, string $reason): Refund
    {
        if (! in_array($order->status, ['succeeded', 'partially_refunded'], true)) {
            throw ValidationException::withMessages(['amount_minor' => __('economy.errors.not_refundable')]);
        }

        $reserved = (int) Refund::query()->where('payment_order_id', $order->id)->whereIn('status', ['requested', 'approved', 'processing', 'requires_review'])->sum('amount_minor');
        if ($amountMinor > $order->amount_minor - $order->refunded_minor - $reserved) {
            throw ValidationException::withMessages(['amount_minor' => __('economy.errors.refund_amount')]);
        }

        $refund = Refund::query()->create([
            'payment_order_id' => $order->id, 'amount_minor' => $amountMinor, 'reason' => $reason,
            'status' => 'requested', 'requested_by' => $actor->id,
        ]);

        $this->audit->record(AuditAction::RefundsRequested, $refund, ['after' => ['order' => $order->order_number, 'amount_minor' => $amountMinor]], $reason, $actor);

        return $refund;
    }

    public function approve(User $actor, Refund $refund): Refund
    {
        if ($refund->requested_by === $actor->id) {
            throw ValidationException::withMessages(['refund' => __('economy.errors.four_eyes')]);
        }
        if ($refund->status !== 'requested') {
            throw ValidationException::withMessages(['refund' => __('economy.errors.not_pending')]);
        }

        $order = $refund->order;
        $credit = intdiv($order->credit_amount * $refund->amount_minor, $order->amount_minor);

        $refund->forceFill(['status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now()])->save();
        $this->audit->record(AuditAction::RefundsApproved, $refund, ['after' => ['status' => 'approved']], $refund->reason, $actor);

        if ($order->wallet->balance < $credit) {
            $refund->forceFill(['status' => 'requires_review'])->save();

            return $refund;
        }

        $refund->forceFill(['status' => 'processing'])->save();
        $result = $this->providers->get($order->provider)->refundPayment($order, $refund);

        if (! $result->succeeded) {
            $refund->forceFill(['status' => 'failed', 'completed_at' => now()])->save();

            return $refund;
        }

        try {
            DB::transaction(function () use ($refund, $order, $credit, $result, $actor): void {
                $transaction = $credit > 0
                    ? $this->ledger->post($order->wallet_id, 'debit', $credit, 'refund', 'refund:'.$refund->ulid,
                        'İade · '.$order->order_number, 'refund', $refund->id, $actor)
                    : null;

                $lockedOrder = PaymentOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                $refunded = $lockedOrder->refunded_minor + $refund->amount_minor;
                $lockedOrder->forceFill([
                    'refunded_minor' => $refunded,
                    'status' => $refunded >= $lockedOrder->amount_minor ? 'refunded' : 'partially_refunded',
                ])->save();

                $refund->forceFill([
                    'status' => 'succeeded', 'provider_refund_id' => $result->providerRefundId,
                    'wallet_transaction_id' => $transaction?->id, 'completed_at' => now(),
                ])->save();
            });
        } catch (LedgerException) {
            // Sağlayıcı iadeyi yaptı ancak kredi bu arada harcandı: manuel inceleme.
            $refund->forceFill(['status' => 'requires_review', 'provider_refund_id' => $result->providerRefundId])->save();

            return $refund;
        }

        $this->audit->record(AuditAction::RefundsCompleted, $refund, ['after' => ['amount_minor' => $refund->amount_minor, 'credit_debited' => $credit]], $refund->reason, $actor);
        $order->user->notify(new PanelNotice('refund_completed', __('economy.notice.refund_completed'), route('panel.transactions')));

        return $refund;
    }

    public function reject(User $actor, Refund $refund, string $note): void
    {
        if (! in_array($refund->status, ['requested', 'requires_review'], true)) {
            throw ValidationException::withMessages(['refund' => __('economy.errors.not_pending')]);
        }

        $refund->forceFill(['status' => 'rejected', 'approved_by' => $actor->id, 'completed_at' => now()])->save();
        $this->audit->record(AuditAction::RefundsRejected, $refund, ['after' => ['status' => 'rejected']], $note, $actor);
    }
}
