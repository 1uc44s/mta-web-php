<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\PaymentOrder;
use App\Models\Refund;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Geliştirme/test sağlayıcısı — docs/ROADMAP.md 9.3, OD-04. Checkout yerel imzalı sahte ödeme ekranına yönlendirir;
 * oradan gelen sonuç, gerçek sağlayıcılarla **aynı** webhook gelen kutusu yolundan (imza doğrulama → payment_events →
 * job) işlenir. Webhook imzası: `X-Fake-Signature = hex(HMAC-SHA256(gövde, APP_KEY türevi))`.
 * Production'da kullanılamaz (`PaymentManager` engeller).
 */
final class FakeProvider implements PaymentProvider
{
    public function key(): string
    {
        return 'fake';
    }

    public static function secret(): string
    {
        return hash_hmac('sha256', 'mrp.fake-payments.v1', (string) config('app.key'));
    }

    public static function sign(string $body): string
    {
        return hash_hmac('sha256', $body, self::secret());
    }

    public function createCheckout(PaymentOrder $order): CheckoutResult
    {
        return new CheckoutResult(
            URL::temporarySignedRoute('payments.fake.show', now()->addMinutes(30), ['order' => $order->ulid]),
            'fake_'.$order->ulid,
        );
    }

    public function verifyWebhook(string $body, array $headers): bool
    {
        $raw = $headers['x-fake-signature'] ?? null;
        $signature = is_array($raw) ? ($raw[0] ?? null) : $raw;

        return is_string($signature) && $signature !== '' && hash_equals(self::sign($body), strtolower($signature));
    }

    public function normalizeEvent(string $body): NormalizedEvent
    {
        $data = json_decode($body, true);

        if (! is_array($data) || ! isset($data['id'], $data['type'], $data['order_id'], $data['amount'], $data['currency'])) {
            throw new InvalidArgumentException('Geçersiz fake webhook gövdesi.');
        }

        return new NormalizedEvent(
            (string) $data['id'],
            match ((string) $data['type']) {
                'charge.succeeded' => 'payment.succeeded',
                'charge.failed' => 'payment.failed',
                default => 'unknown',
            },
            (string) $data['order_id'],
            (int) $data['amount'],
            strtoupper((string) $data['currency']),
            array_intersect_key($data, array_flip(['id', 'type', 'order_id', 'amount', 'currency', 'failure_code'])),
        );
    }

    /** Sahte ödeme ekranının ürettiği webhook gövdesi (imzası `sign()` ile). */
    public static function eventBody(PaymentOrder $order, string $outcome): string
    {
        return (string) json_encode([
            'id' => 'evt_fake_'.Str::lower((string) Str::ulid()),
            'type' => $outcome === 'succeeded' ? 'charge.succeeded' : 'charge.failed',
            'order_id' => $order->provider_order_id,
            'amount' => $order->amount_minor,
            'currency' => $order->currency,
            'failure_code' => $outcome === 'succeeded' ? null : 'card_declined',
        ], JSON_THROW_ON_ERROR);
    }

    public function getPaymentStatus(PaymentOrder $order): string
    {
        return $order->status;
    }

    public function refundPayment(PaymentOrder $order, Refund $refund): RefundResult
    {
        return new RefundResult(true, 'rf_fake_'.$refund->ulid);
    }
}
