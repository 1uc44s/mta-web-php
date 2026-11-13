<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\PaymentOrder;
use App\Models\Refund;

/**
 * Ödeme sağlayıcı sözleşmesi — docs/SRS.md FR-PAY-001, ARCHITECTURE §1 (`Services/Payments`).
 * Gerçek sağlayıcı (iyzico/PayTR/Stripe…) OD-04 kararından sonra bu arayüzle eklenir; geliştirme ve testler `FakeProvider`.
 */
interface PaymentProvider
{
    public function key(): string;

    /** Sağlayıcıda ödeme oturumu açar; kullanıcı `redirectUrl`'e 303 ile yönlendirilir. */
    public function createCheckout(PaymentOrder $order): CheckoutResult;

    /**
     * Ham gövde + başlıklar üzerinden imza doğrulaması (sabit zamanlı karşılaştırma).
     *
     * @param  array<string, list<string|null>|string|null>  $headers  Küçük harfli başlık adları
     */
    public function verifyWebhook(string $body, array $headers): bool;

    /** İmzası doğrulanmış gövdeyi normalize eder; kart/kişisel veri `payload` dışında bırakılır. */
    public function normalizeEvent(string $body): NormalizedEvent;

    /** `created|pending|succeeded|failed` — mutabakat ve dönüş sayfası teşhisi için. */
    public function getPaymentStatus(PaymentOrder $order): string;

    public function refundPayment(PaymentOrder $order, Refund $refund): RefundResult;
}
