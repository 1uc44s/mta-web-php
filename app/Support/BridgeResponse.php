<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Köprü yanıt biçimi — docs/ROUTES.md §7.2: `{success, data|error, requestId}`. Hata kodları köprüye özgüdür
 * (SIGNATURE_INVALID, REPLAY_DETECTED, LINK_CODE_INVALID, ACCOUNT_ALREADY_LINKED …). Yanıtlar asla serial, e-posta,
 * IP veya token içermez (FR-BRG-012).
 */
final class BridgeResponse
{
    /** @param  array<string, mixed>  $data */
    public static function ok(array $data = [], int $status = 200): JsonResponse
    {
        return new JsonResponse(['success' => true, 'data' => $data, 'requestId' => RequestId::current()], $status);
    }

    public static function error(int $status, string $code, string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => ['code' => $code, 'message' => $message],
            'requestId' => RequestId::current(),
        ], $status);
    }
}
