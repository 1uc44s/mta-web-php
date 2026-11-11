<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Eksik veya güvensiz yapılandırmayı açılışta anlamlı bir hatayla durdurur (docs/ROADMAP.md Faz 1.5).
 */
final class ConfigValidator
{
    /** @return list<string> */
    public static function problems(): array
    {
        $problems = [];

        if (blank(config('app.key'))) {
            $problems[] = 'APP_KEY boş. `php artisan key:generate` çalıştır.';
        }

        if (! in_array(config('database.default'), ['mariadb', 'mysql'], true)) {
            $problems[] = 'DB_CONNECTION mariadb veya mysql olmalı (docs/DATABASE.md §1.1).';
        }

        if (config('hashing.driver') !== 'argon2id') {
            $problems[] = 'HASH_DRIVER argon2id olmalı (docs/SRS.md FR-AUTH-003).';
        }

        $pepper = config('roleplay.bridge.serial_pepper');
        if (! is_string($pepper) || preg_match('/^[0-9a-f]{64}$/', $pepper) !== 1) {
            $problems[] = 'BRIDGE_SERIAL_PEPPER 64 karakterlik hex olmalı (32 rastgele byte).';
        }

        if (app()->isProduction() && config('app.debug') === true) {
            $problems[] = 'Production ortamında APP_DEBUG=false olmalı (docs/SRS.md §8).';
        }

        return $problems;
    }

    public static function assertValid(): void
    {
        $problems = self::problems();

        if ($problems !== []) {
            throw new RuntimeException("Yapılandırma hatalı:\n- ".implode("\n- ", $problems));
        }
    }
}
