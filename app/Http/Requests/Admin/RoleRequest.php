<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Özel (sistem dışı) rol oluşturma/güncelleme — docs/ROUTES.md §6.10, ADMIN §2. Öncelik 1–89 (sistem rolleri üstte). */
final class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $role = $this->route('role');
        $ignore = $role instanceof Role ? $role->id : null;

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('roles', 'slug')->ignore($ignore)],
            'description' => ['nullable', 'string', 'max:500'],
            'priority' => ['required', 'integer', 'min:1', 'max:89'],
            'color' => ['nullable', 'in:accent,amber,info,success,neutral'],
            'is_staff' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(array_keys((array) config('permissions')))],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [...(array) trans('admin.roles.fields'), 'reason' => __('admin.reason')];
    }
}
