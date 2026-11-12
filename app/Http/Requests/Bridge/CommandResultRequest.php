<?php

declare(strict_types=1);

namespace App\Http\Requests\Bridge;

/** POST /api/v1/game/commands/result — docs/ROUTES.md §7.2, SRS FR-BRG-011. */
final class CommandResultRequest extends BridgeRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'regex:/^[0-9A-Za-z]{26}$/'],
            'status' => ['required', 'string', 'in:succeeded,failed'],
            'detail' => ['nullable', 'array'],
            'detail.applied_at' => ['nullable', 'string', 'max:40'],
            'detail.error' => ['nullable', 'required_if:status,failed', 'string', 'in:player_offline,unsupported_command,invalid_payload,timeout,failed'],
            'detail.message' => ['nullable', 'string', 'max:255'],
        ];
    }
}
