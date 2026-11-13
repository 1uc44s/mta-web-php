<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Settings\SettingsRepository;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Marka kimliği — docs/DESIGN.md §2, ADMIN §7. Ad, kısa ad, slogan, logo ve sekme ikonu ACP → Marka ekranından gelir;
 * kodda marka adı sabit yazılmaz. Ayar okunamazsa (kurulum, migration öncesi) config varsayılanına düşer.
 */
final class Brand
{
    /** Üretilen ikon boyutları (px): sekme, Apple dokunma, PWA. */
    public const ICON_SIZES = [32, 180, 192, 512];

    public static function name(): string
    {
        return self::string('site.name') ?? (string) config('roleplay.brand.name', 'Roleplay');
    }

    public static function shortName(): string
    {
        return self::string('site.short_name') ?? (string) config('roleplay.brand.short_name', self::name());
    }

    public static function tagline(): ?string
    {
        return self::string('site.tagline');
    }

    public static function logoUrl(): ?string
    {
        $path = self::string('site.logo_path');

        return $path === null ? null : Storage::disk('public')->url($path);
    }

    public static function hasCustomIcon(): bool
    {
        return self::string('site.icon_set') !== null;
    }

    /** Yüklenmiş ikon setinden istenen boyut; yoksa paketle gelen varsayılan ikon. */
    public static function iconUrl(int $size): string
    {
        $set = self::string('site.icon_set');
        if ($set !== null && in_array($size, self::ICON_SIZES, true)) {
            return Storage::disk('public')->url($set.'/icon-'.$size.'.png');
        }

        return match (true) {
            $size <= 64 => asset('favicon.svg'),
            $size >= 512 => asset('icons/icon-512.png'),
            default => asset('icons/icon-192.png'),
        };
    }

    /** CMS metnindeki `{site}` ve `{site_short}` yer tutucularını güncel marka adıyla değiştirir (docs/CMS.md Ek). */
    public static function fill(string $text): string
    {
        if (! str_contains($text, '{site')) {
            return $text;
        }

        return strtr($text, ['{site}' => self::name(), '{site_short}' => self::shortName()]);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function fillArray(array $data): array
    {
        array_walk_recursive($data, function (mixed &$value): void {
            if (is_string($value)) {
                $value = self::fill($value);
            }
        });

        return $data;
    }

    private static function string(string $key): ?string
    {
        try {
            $value = app(SettingsRepository::class)->get($key);
        } catch (Throwable) {
            return null; // ayar tablosu henüz yok (kurulum / migration)
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
