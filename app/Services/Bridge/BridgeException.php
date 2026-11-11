<?php

declare(strict_types=1);

namespace App\Services\Bridge;

use RuntimeException;

/** Köprü uçlarında beklenen iş kuralı hatası — controller bunu `BridgeResponse::error` biçimine çevirir (ROUTES §7.2). */
final class BridgeException extends RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}
