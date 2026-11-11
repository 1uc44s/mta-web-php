<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Services\Content\ContentResourceRegistry;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CMS kaynak formu — kurallar ContentResourceRegistry alan tanımlarından üretilir (docs/ROUTES.md §6.8, CMS §3.1).
 * Kaynak anahtarı rota `defaults('resource')` değerinden okunur.
 */
final class ContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function resourceKey(): string
    {
        return (string) $this->route('resource');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $def = ContentResourceRegistry::get($this->resourceKey());
        /** @var Model $model */
        $model = new $def['model'];
        $id = $this->route('id');
        $rules = [];

        foreach ($def['fields'] as $name => $field) {
            $base = $field['rules'] ?? [];
            $rules[$name] = match ($field['type']) {
                'bool' => ['boolean'],
                'media' => ['nullable', 'integer', 'exists:media,id'],
                'belongs' => [...($base !== [] ? $base : ['nullable']), 'integer', Rule::exists((new $field['model'])->getTable(), 'id')],
                'select' => [...($base !== [] ? $base : ['nullable']), Rule::in(array_keys($field['options'] ?? []))],
                'tags', 'rules' => ['nullable', 'string', 'max:500'],
                'channels' => ['array'],
                default => $base !== [] ? $base : ['nullable', 'string'],
            };
            if ($field['type'] === 'channels') {
                $rules[$name.'.*'] = [Rule::in(array_keys($field['options'] ?? []))];
            }
        }

        if ($def['slug_from'] !== null) {
            $rules['slug'] = ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(Slug::RESERVED),
                Rule::unique($model->getTable(), 'slug')->ignore($id)];
        }

        if (in_array('seo_title', $model->getFillable(), true)) {
            $rules['seo_title'] = ['nullable', 'string', 'max:70'];
            $rules['meta_description'] = ['nullable', 'string', 'max:170'];
        }

        if ($def['publishable']) {
            $rules['published_at'] = ['nullable', 'date'];
        }

        if (isset($def['revisions'])) {
            $rules['revision_note'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $labels = array_map(fn (array $f): string => $f['label'], ContentResourceRegistry::get($this->resourceKey())['fields']);

        return $labels + ['slug' => (string) __('content_admin.slug'), 'published_at' => (string) __('content_admin.published_at')];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        foreach (ContentResourceRegistry::get($this->resourceKey())['fields'] as $name => $field) {
            if ($field['type'] === 'bool') {
                $merge[$name] = $this->boolean($name);
            }
            if ($field['type'] === 'channels' && ! $this->has($name)) {
                $merge[$name] = [];
            }
        }
        $this->merge($merge);
    }
}
