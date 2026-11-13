<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use RuntimeException;

/**
 * Ledger iş kuralı hatası — `INSUFFICIENT_BALANCE`, `WALLET_FROZEN`, `IDEMPOTENCY_CONFLICT`, `ALREADY_REVERSED`…
 * Çağıran Action kullanıcıya anlamlı bir doğrulama mesajına çevirir.
 */
final class LedgerException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
