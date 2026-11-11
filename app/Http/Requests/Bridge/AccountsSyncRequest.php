<?php

declare(strict_types=1);

namespace App\Http\Requests\Bridge;

/** POST /api/v1/game/accounts/sync — docs/ROUTES.md §7.2 (en fazla 100 hesap). */
final class AccountsSyncRequest extends BridgeRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...self::eventRules(),
            'accounts' => ['required', 'array', 'min:1', 'max:100'],
            'accounts.*.ref' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.:-]+$/'],
            'accounts.*.name' => ['required', 'string', 'max:64'],
            'accounts.*.last_seen_at' => ['nullable', 'date'],
        ];
    }
}
