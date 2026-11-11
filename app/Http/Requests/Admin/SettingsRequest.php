<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Services\Settings\SettingsRepository;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /admin/settings/{group} — kurallar config/settings.php türünden üretilir (docs/ADMIN.md §7).
 * Yalnızca bu gruba ait anahtarlar kabul edilir; checkbox'lar gönderilmezse false sayılır.
 */
final class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array{group: string, type: string, default: mixed, public: bool, super?: bool}> */
    public function groupCatalog(): array
    {
        $group = (string) $this->route('group');

        return array_filter(app(SettingsRepository::class)->catalog(), fn (array $m): bool => $m['group'] === $group);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->groupCatalog() as $key => $meta) {
            $field = self::field($key);
            $rules[$field] = match ($meta['type']) {
                'bool' => ['boolean'],
                'int' => ['nullable', 'integer', 'min:0', 'max:100000000'],
                'url' => ['nullable', 'url:https', 'max:500'],
                'json' => ['nullable', 'json', 'max:5000'],
                default => ['nullable', 'string', 'max:1000'],
            };
        }

        return $rules;
    }

    /** Ayar anahtarları noktalıdır; form alanı adı `site__name` biçimindedir. */
    public static function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    /** @return array<string, mixed> Ayar anahtarı => tip dönüştürülmüş değer */
    public function settings(): array
    {
        $data = $this->validated();
        $out = [];

        foreach ($this->groupCatalog() as $key => $meta) {
            $value = $data[self::field($key)] ?? null;
            $out[$key] = match ($meta['type']) {
                'bool' => (bool) $value,
                'int' => $value === null || $value === '' ? null : (int) $value,
                'json' => $value === null || $value === '' ? null : json_decode((string) $value, true),
                default => $value === '' ? null : $value,
            };
        }

        return $out;
    }

    protected function prepareForValidation(): void
    {
        foreach ($this->groupCatalog() as $key => $meta) {
            if ($meta['type'] === 'bool') {
                $this->merge([self::field($key) => $this->boolean(self::field($key))]);
            }
        }
    }
}
