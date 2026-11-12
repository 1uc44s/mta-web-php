<?php

declare(strict_types=1);

namespace App\Http\Requests\Bridge;

/**
 * POST /api/v1/game/device-link/verify — docs/ROUTES.md §7.2, ARCHITECTURE §7.6. `serial` (MTA: 32 hex) yalnızca HMAC
 * hesabında kullanılır; doğrulama hatası mesajları değeri içermez.
 */
final class DeviceLinkVerifyRequest extends BridgeRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...self::eventRules(),
            'code' => ['required', 'string', 'max:16'],
            'account' => ['required', 'array'],
            'account.ref' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.:-]+$/'],
            'serial' => ['required', 'string', 'regex:/^[0-9A-Fa-f]{32}$/'],
        ];
    }
}
