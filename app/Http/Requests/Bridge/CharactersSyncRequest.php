<?php

declare(strict_types=1);

namespace App\Http\Requests\Bridge;

/** POST /api/v1/game/characters/sync — docs/ROUTES.md §7.2. `public_profile` allowlist'i Action'da uygulanır. */
final class CharactersSyncRequest extends BridgeRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...self::eventRules(),
            'account_ref' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.:-]+$/'],
            'characters' => ['present', 'array', 'max:20'],
            'characters.*.ref' => ['required', 'string', 'max:64', 'distinct'],
            'characters.*.name' => ['required', 'string', 'max:64'],
            'characters.*.status' => ['nullable', 'string', 'max:32'],
            'characters.*.level' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'characters.*.playtime_minutes' => ['nullable', 'integer', 'min:0'],
            'characters.*.faction_key' => ['nullable', 'string', 'max:64'],
            'characters.*.job_key' => ['nullable', 'string', 'max:64'],
            'characters.*.public_profile' => ['nullable', 'array'],
            'characters.*.created_at' => ['nullable', 'date'],
            'characters.*.last_seen_at' => ['nullable', 'date'],
        ];
    }
}
