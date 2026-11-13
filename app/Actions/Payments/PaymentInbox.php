<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Jobs\ProcessPaymentEvent;
use App\Models\PaymentEvent;
use App\Models\PaymentOrder;
use App\Services\Payments\PaymentManager;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Webhook gelen kutusu — docs/SRS.md FR-PAY-003, ROUTES §7.3.
 *   imza doğrulama → normalize → `payment_events` INSERT (unique `provider + provider_event_id`) → işleme job'u.
 * İmzası geçersiz istek **kaydedilmez** (saldırgan gerçek olay kimliklerini önceden "tüketemez"); `security` loguna yazılır.
 * Aynı olay ikinci kez gelirse yeni kayıt/iş oluşmaz (tek kredi garantisinin ilk katmanı; ikincisi ledger idempotency).
 */
final class PaymentInbox
{
    public function __construct(private readonly PaymentManager $providers) {}

    /**
     * @param  array<string, list<string|null>|string|null>  $headers
     * @return array{accepted: bool, duplicate: bool}
     */
    public function receive(string $providerKey, string $body, array $headers): array
    {
        if (! $this->providers->exists($providerKey)) {
            return ['accepted' => false, 'duplicate' => false];
        }

        $provider = $this->providers->get($providerKey);

        if (! $provider->verifyWebhook($body, $headers)) {
            Log::channel('security')->warning('payments.signature_invalid', ['provider' => $providerKey]);

            return ['accepted' => false, 'duplicate' => false];
        }

        try {
            $event = $provider->normalizeEvent($body);
        } catch (InvalidArgumentException) {
            Log::channel('security')->warning('payments.payload_invalid', ['provider' => $providerKey]);

            return ['accepted' => false, 'duplicate' => false];
        }

        $order = PaymentOrder::query()->where('provider', $providerKey)->where('provider_order_id', $event->providerOrderId)->first();

        try {
            $record = PaymentEvent::query()->create([
                'provider' => $providerKey,
                'provider_event_id' => mb_substr($event->eventId, 0, 128),
                'payment_order_id' => $order?->id,
                'event_type' => $event->type,
                'signature_valid' => true,
                'payload' => $event->payload,
                'status' => 'received',
                'received_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return ['accepted' => true, 'duplicate' => true];
        }

        ProcessPaymentEvent::dispatch($record->id);

        return ['accepted' => true, 'duplicate' => false];
    }
}
