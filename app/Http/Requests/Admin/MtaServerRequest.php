<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** MTA sunucusu oluşturma/güncelleme — docs/ROUTES.md §6.11, DATABASE §4.2. Yetki rota `can:integrations.manage`. */
final class MtaServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_maintenance' => $this->boolean('is_maintenance'),
            'is_primary' => $this->boolean('is_primary'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('server');

        return [
            'slug' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9-]+$/', Rule::unique('mta_servers', 'slug')->ignore(is_numeric($id) ? (int) $id : null)],
            'name' => ['required', 'string', 'max:100'],
            'connect_host' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.-]+$/'],
            'connect_port' => ['required', 'integer', 'between:1,65535'],
            'max_players' => ['nullable', 'integer', 'between:1,5000'],
            'is_maintenance' => ['boolean'],
            'maintenance_note' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['boolean'],
            'is_active' => ['boolean'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [...__('game.admin.fields'), 'reason' => __('admin.reason')];
    }
}
