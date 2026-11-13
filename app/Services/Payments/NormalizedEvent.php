<?php

declare(strict_types=1);

namespace App\Services\Payments;

/**
 * Sağlayıcıdan bağımsız webhook olayı — docs/DATABASE.md §6.6 `event_type`:
 * `payment.succeeded`, `payment.failed`, `refund.succeeded`, `refund.failed`.
 */
final readonly class NormalizedEvent
{
    /** @param  array<string, mixed>  $payload  Temizlenmiş, saklanabilir alanlar */
    public function __construct(
        public string $eventId,
        public string $type,
        public string $providerOrderId,
        public int $amountMinor,
        public string $currency,
        public array $payload,
    ) {}
}
