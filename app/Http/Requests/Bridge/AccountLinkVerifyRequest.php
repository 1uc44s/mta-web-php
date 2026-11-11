<?php

declare(strict_types=1);

namespace App\Http\Requests\Bridge;

/** POST /api/v1/game/account-link/verify — docs/ROUTES.md §7.2, ARCHITECTURE §7.4. Kod biçimi Action'da doğrulanır (sebep sızdırmamak için). */
final class AccountLinkVerifyRequest extends BridgeRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...self::eventRules(),
            'code' => ['required', 'string', 'max:16'],
            ...self::accountRules(),
        ];
    }
}
