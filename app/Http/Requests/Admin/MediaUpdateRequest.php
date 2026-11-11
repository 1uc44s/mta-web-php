<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** POST /admin/media/{media} — alt metin ve dekoratif bayrağı (docs/ROUTES.md §6.8). */
final class MediaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'is_decorative' => ['boolean'],
            'alt_text' => ['exclude_if:is_decorative,true', 'required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_decorative' => $this->boolean('is_decorative')]);
    }
}
