<?php

declare(strict_types=1);

namespace App\Services\Payments;

/** `PaymentProvider::createCheckout` sonucu. */
final readonly class CheckoutResult
{
    public function __construct(public string $redirectUrl, public string $providerOrderId) {}
}
