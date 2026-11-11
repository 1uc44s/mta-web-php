<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** POST /admin/users/{user}/status — docs/ROUTES.md §6.2, FR-ADM-006. */
final class SuspendUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:suspend,unsuspend'],
            'until' => ['nullable', 'date', 'after:now', 'exclude_unless:action,suspend'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['reason' => __('admin.reason'), 'until' => __('admin.users.suspend_until')];
    }
}
