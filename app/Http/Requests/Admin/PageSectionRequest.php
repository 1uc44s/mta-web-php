<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Services\Content\SectionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Bölüm ekleme / güncelleme — docs/ROUTES.md §6.8. Güncellemede veri JSON olarak gelir; şema doğrulaması
 * ManagePageSections::update içinde (türün şemasıyla) yapılır.
 */
final class PageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        if ($this->route('section') === null) {
            return ['type' => ['required', Rule::in(SectionRegistry::TYPES)]];
        }

        return [
            'data' => ['required', 'json', 'max:40000'],
            'is_visible' => ['boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function sectionData(): array
    {
        $decoded = json_decode((string) $this->validated('data'), true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('section') !== null) {
            $this->merge(['is_visible' => $this->boolean('is_visible')]);
        }
    }
}
