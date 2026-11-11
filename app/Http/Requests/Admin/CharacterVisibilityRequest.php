<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Karakter profil görünürlüğü — docs/ROUTES.md §6.6 (gerekçe zorunlu, audit `characters.visibility_changed`). */
final class CharacterVisibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['hidden' => $this->boolean('hidden')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'hidden' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['reason' => __('admin.reason')];
    }
}
