<?php

declare(strict_types=1);

namespace App\Services\Payments;

/** `PaymentProvider::refundPayment` sonucu. */
final readonly class RefundResult
{
    public function __construct(public bool $succeeded, public ?string $providerRefundId, public ?string $error = null) {}
}
