<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Tema ve efekt tercihi (docs/DESIGN.md §4). Misafir için cookie, girişli kullanıcı için hesap (Faz 6).
 * Cookie'ler şifrelenmez (bootstrap/app.php), çünkü değerleri allowlist ile doğrulanır ve gizli değildir.
 */
final class ThemePreference
{
    public const THEME_COOKIE = 'mrp_theme';

    public const EFFECTS_COOKIE = 'mrp_effects';

    public const THEMES = ['dark', 'light', 'system'];

    public const EFFECTS = ['full', 'low'];

    public static function theme(): string
    {
        $user = auth()->user();
        $value = $user !== null && is_string($user->getAttribute('theme'))
            ? $user->getAttribute('theme')
            : request()->cookie(self::THEME_COOKIE);

        return in_array($value, self::THEMES, true) ? $value : 'dark';
    }

    public static function effects(): string
    {
        $user = auth()->user();
        $value = $user !== null && $user->getAttribute('reduced_effects') !== null
            ? ($user->getAttribute('reduced_effects') ? 'low' : 'full')
            : request()->cookie(self::EFFECTS_COOKIE);

        return in_array($value, self::EFFECTS, true) ? $value : 'full';
    }
}
