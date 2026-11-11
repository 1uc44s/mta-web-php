<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** POST /admin/content/rules/bump-version — docs/CMS.md §5.3 (değişiklik notu zorunlu). */
final class RulesVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'max:32', 'regex:/^\d{4}\.\d{1,2}\.\d{1,3}$/'],
            'note' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['version' => __('content_admin.rules_version.new'), 'note' => __('content_admin.rules_version.note')];
    }
}
