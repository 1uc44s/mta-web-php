<?php

declare(strict_types=1);

namespace App\Http\Requests\Bridge;

/** POST /api/v1/game/player/{connected,disconnected} — docs/ROUTES.md §7.2. */
final class PlayerEventRequest extends BridgeRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...self::eventRules(),
            'occurred_at' => ['required', 'date'],
            ...self::accountRules(),
            'session_seconds' => ['nullable', 'integer', 'min:0', 'max:604800'],
        ];
    }
}
