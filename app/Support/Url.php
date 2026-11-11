<?php

declare(strict_types=1);

namespace App\Support;

/**
 * URL güvenliği (docs/SRS.md SEC-026, docs/RULES.md §6–7).
 */
final class Url
{
    /** Yalnızca aynı origin'e göreli yol kabul eder; aksi halde varsayılana döner (açık yönlendirme yok). */
    public static function safeRedirect(mixed $target, string $fallback): string
    {
        if (! is_string($target) || $target === '' || ! str_starts_with($target, '/') || str_starts_with($target, '//')
            || str_contains($target, '\\') || preg_match('/[\x00-\x1F]/', $target) === 1) {
            return $fallback;
        }

        return $target;
    }

    /** Kullanıcı kaynaklı bağlantılar için şema allowlist'i: https, http, mailto. Diğerleri '#' olur. */
    public static function safe(?string $url): string
    {
        $url = trim((string) $url);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['https', 'http', 'mailto'], true) ? $url : '#';
    }
}
