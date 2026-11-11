<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** POST /admin/branches/{branch}/members — docs/ADMIN.md §5.3. Kullanıcı adı veya e-posta ile seçilir. */
final class BranchMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user' => ['required', 'string', 'max:254'],
            'is_lead' => ['boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['user' => __('admin.staff.user'), 'expires_at' => __('admin.expires_at')];
    }
}
