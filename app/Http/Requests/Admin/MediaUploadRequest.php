<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /admin/media — docs/CMS.md §3.3: uzantı + finfo MIME, ≤ 8 MB, SVG yasak. Alt metin zorunlu (dekoratif değilse).
 */
final class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:8192', 'mimes:jpg,jpeg,png,webp,gif', 'mimetypes:image/jpeg,image/png,image/webp,image/gif', 'dimensions:min_width=16,min_height=16,max_width=12000,max_height=12000'],
            'is_decorative' => ['boolean'],
            'alt_text' => ['exclude_if:is_decorative,true', 'required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['file' => 'Görsel', 'alt_text' => 'Alternatif metin'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_decorative' => $this->boolean('is_decorative')]);
    }
}
