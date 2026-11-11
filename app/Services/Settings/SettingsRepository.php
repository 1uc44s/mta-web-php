<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Çalışma zamanı ayarları (docs/ADMIN.md §7). Katalog config/settings.php'dedir; bilinmeyen anahtar yazılamaz.
 * Tüm değerler tek cache kaydında tutulur, yazımda temizlenir.
 */
final class SettingsRepository
{
    private const CACHE_KEY = 'settings:v1';

    /** @var array<string, mixed>|null */
    private ?array $memo = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function bool(string $key): bool
    {
        return (bool) $this->get($key, false);
    }

    /** @return array<string, mixed> Katalogdaki tüm anahtarlar (DB değeri yoksa varsayılan). */
    public function all(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $stored = Cache::rememberForever(self::CACHE_KEY, fn (): array => SystemSetting::query()
            ->get(['key', 'value'])
            ->mapWithKeys(fn (SystemSetting $s): array => [$s->key => $s->getAttribute('value')])
            ->all());

        $values = [];
        foreach ($this->catalog() as $key => $definition) {
            $values[$key] = array_key_exists($key, $stored) ? $stored[$key] : $definition['default'];
        }

        return $this->memo = $values;
    }

    /** @return array<string, mixed> Yalnızca public işaretli ayarlar (view'lara paylaşılır). */
    public function public(): array
    {
        return array_intersect_key($this->all(), array_filter($this->catalog(), fn (array $d): bool => $d['public'] ?? false));
    }

    public function set(string $key, mixed $value, ?int $updatedBy = null): void
    {
        $definition = $this->catalog()[$key] ?? throw new InvalidArgumentException("Bilinmeyen ayar: {$key}");

        SystemSetting::query()->updateOrCreate(['key' => $key], [
            'group' => $definition['group'],
            'type' => $definition['type'],
            'value' => $value,
            'is_public' => $definition['public'] ?? false,
            'is_secret' => $definition['secret'] ?? false,
            'updated_by' => $updatedBy,
        ]);

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->memo = null;
    }

    /** @return array<string, array{group: string, type: string, default: mixed, public?: bool, secret?: bool, super?: bool}> */
    public function catalog(): array
    {
        /** @var array<string, array{group: string, type: string, default: mixed, public?: bool, secret?: bool, super?: bool}> */
        return config('settings', []);
    }
}
