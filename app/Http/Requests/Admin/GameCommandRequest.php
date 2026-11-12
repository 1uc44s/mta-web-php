<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Elle oyun komutu — docs/ROUTES.md §6.7 `admin.game-commands.store`, ARCHITECTURE §7.5. Tür bazlı izin controller'da
 * (`config/roleplay.php bridge.commands`). `account.link_revoked` yalnızca sistem tarafından kuyruğa alınır.
 */
final class GameCommandRequest extends FormRequest
{
    /** @var list<string> */
    public const TYPES = ['player.warn', 'player.kick', 'player.mute', 'player.jail', 'player.ban', 'player.unban', 'server.announce'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = (string) $this->input('type');

        return [
            'type' => ['required', 'string', Rule::in(self::TYPES)],
            'mta_server_id' => ['required', 'integer', 'exists:mta_servers,id'],
            'account' => [Rule::requiredIf($type !== 'server.announce'), 'nullable', 'string', 'regex:/^[0-9a-z]{26}$/'],
            'message' => [Rule::requiredIf(in_array($type, ['player.warn', 'player.kick', 'player.mute', 'player.jail', 'player.ban', 'server.announce'], true)), 'nullable', 'string', 'max:200'],
            'minutes' => [Rule::requiredIf(in_array($type, ['player.mute', 'player.jail'], true)), 'nullable', 'integer', 'min:1', 'max:525600'],
        ];
    }
}
