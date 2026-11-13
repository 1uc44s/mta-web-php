<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/** POST /admin/branding — docs/ROUTES.md §6, DESIGN §2. SVG kabul edilmez; görseller sunucuda yeniden kodlanır. */
final class BrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'short_name' => ['required', 'string', 'min:2', 'max:30'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'file', 'max:2048', 'mimes:png,jpg,jpeg,webp', 'mimetypes:image/png,image/jpeg,image/webp'],
            'icon' => ['nullable', 'file', 'max:1024', 'mimes:png,jpg,jpeg,webp', 'mimetypes:image/png,image/jpeg,image/webp', 'dimensions:min_width=64,min_height=64'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'Site adı', 'short_name' => 'Kısa ad', 'tagline' => 'Slogan', 'logo' => 'Logo', 'icon' => 'Sekme ikonu'];
    }

    /** @return array{name: string, short_name: string, tagline: string|null} */
    public function text(): array
    {
        $tagline = trim((string) $this->validated('tagline', ''));

        return [
            'name' => trim((string) $this->validated('name')),
            'short_name' => trim((string) $this->validated('short_name')),
            'tagline' => $tagline === '' ? null : $tagline,
        ];
    }

    public function upload(string $field): ?UploadedFile
    {
        $file = $this->validated($field);

        return $file instanceof UploadedFile ? $file : null;
    }
}
