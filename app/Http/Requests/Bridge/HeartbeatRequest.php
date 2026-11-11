<?php

declare(strict_types=1);

namespace App\Http\Requests\Bridge;

/** POST /api/v1/game/heartbeat — docs/ROUTES.md §7.2, SRS FR-BRG-009. */
final class HeartbeatRequest extends BridgeRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'players' => ['required', 'integer', 'min:0', 'max:5000'],
            'max_players' => ['required', 'integer', 'min:1', 'max:5000'],
            'server_name' => ['nullable', 'string', 'max:120'],
            'game_mode' => ['nullable', 'string', 'max:64'],
            'map' => ['nullable', 'string', 'max:64'],
            'resource_version' => ['nullable', 'string', 'max:32'],
            'uptime_sec' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
