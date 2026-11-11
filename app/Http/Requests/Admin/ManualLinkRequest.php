<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Manuel hesap bağlama — docs/ROUTES.md §6.6 (gerekçe + kanıt zorunlu). Yetki rota `can:game_accounts.manage`. */
final class ManualLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:32', 'exists:users,username'],
            'mta_server_id' => ['required', 'integer', 'exists:mta_servers,id'],
            'mta_account_ref' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_.:-]+$/'],
            'account_name' => ['required', 'string', 'max:64'],
            'evidence' => ['required', 'string', 'min:5', 'max:1000'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'username' => __('game.admin.username'), 'mta_server_id' => __('game.admin.server'),
            'mta_account_ref' => __('game.admin.ref'), 'account_name' => __('game.admin.name'),
            'evidence' => __('game.admin.evidence'), 'reason' => __('admin.reason'),
        ];
    }
}
