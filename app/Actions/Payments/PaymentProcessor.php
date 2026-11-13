<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\AuditAction;
use App\Models\PaymentEvent;
use App\Models\PaymentOrder;
use App\Notifications\PanelNotice;
use App\Services\Audit\AuditLogger;
use App\Services\Wallet\LedgerService;
use Illuminate\Support\Facades\DB;

/**
 * Webhook olayının işlenmesi — docs/SRS.md FR-PAY-003. Sipariş kilitlenir (FOR UPDATE); tutar ve para birimi siparişle
 * eşleşmezse olay `failed` olur ve bakiye **yüklenmez**. `payment.succeeded` → ledger credit
 * (`idempotency_key = pay:{order_ulid}`) — aynı ödeme için ikinci olay gelse bile tek kredi oluşur.
 */
final class PaymentProcessor
{
    public function __construct(private readonly LedgerService $ledger, private readonly AuditLogger $audit) {}

    public function process(int $eventId): void
    {
        $credited = null;

        DB::transaction(function () use ($eventId, &$credited): void {
            $event = PaymentEvent::query()->whereKey($eventId)->lockForUpdate()->first();
            if ($event === null || $event->status !== 'received') {
                return;
            }

            $order = $event->payment_order_id !== null
                ? PaymentOrder::query()->whereKey($event->payment_order_id)->lockForUpdate()->first()
                : null;

            if ($order === null) {
                $this->finish($event, 'ignored', 'order_not_found');

                return;
            }

            $amount = (int) ($event->payload['amount'] ?? -1);
            $currency = strtoupper((string) ($event->payload['currency'] ?? ''));
            if ($amount !== $order->amount_minor || $currency !== $order->currency) {
                $this->finish($event, 'failed', 'amount_mismatch');

                return;
            }

            switch ($event->event_type) {
                case 'payment.succeeded':
                    if (! $order->isPending() && $order->status !== 'failed') {
                        $this->finish($event, 'ignored', 'already_'.$order->status);

                        return;
                    }

                    $transaction = $this->ledger->post($order->wallet_id, 'credit', $order->credit_amount, 'topup', 'pay:'.$order->ulid,
                        'Kredi yükleme · '.$order->order_number, 'payment_order', $order->id);
                    $order->forceFill(['status' => 'succeeded', 'paid_at' => now(), 'failure_reason' => null])->save();
                    $this->finish($event, 'processed');
                    $credited = [$order, $transaction];
                    break;

                case 'payment.failed':
                    if ($order->isPending()) {
                        $order->forceFill(['status' => 'failed', 'failure_reason' => mb_substr((string) ($event->payload['failure_code'] ?? 'failed'), 0, 255)])->save();
                    }
                    $this->finish($event, 'processed');
                    break;

                default:
                    $this->finish($event, 'ignored', 'unsupported_event');
            }
        });

        if ($credited !== null) {
            [$order, $transaction] = $credited;
            $this->audit->record(AuditAction::PaymentsSucceeded, $order, [
                'after' => ['order' => $order->order_number, 'amount_minor' => $order->amount_minor, 'credit' => $order->credit_amount, 'transaction' => $transaction->ulid],
            ], null, null, 'payment_provider');
            $order->user->notify(new PanelNotice('payment_succeeded', __('economy.notice.payment_succeeded', ['amount' => $order->credit_amount]), route('panel.payments.show', $order)));
        }
    }

    private function finish(PaymentEvent $event, string $status, ?string $error = null): void
    {
        $event->forceFill(['status' => $status, 'error' => $error, 'processed_at' => now()])->save();
    }
}
