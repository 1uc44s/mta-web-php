<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Branş oluşturma/güncelleme — docs/DATABASE.md §3.6, ROUTES §6.10. */
final class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $branch = $this->route('branch');
        $ignore = $branch instanceof Branch ? $branch->id : null;

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('branches', 'slug')->ignore($ignore)],
            'type' => ['required', Rule::in(Branch::TYPES)],
            'parent_id' => ['nullable', 'integer', 'exists:branches,id', Rule::notIn(array_filter([$ignore]))],
            'faction_id' => ['nullable', 'integer', 'exists:factions,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return (array) trans('admin.branches.fields');
    }
}
