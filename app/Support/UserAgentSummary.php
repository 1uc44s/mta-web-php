<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Kullanıcı ajanının kaba özeti: "Chrome · Windows" (docs/SRS.md FR-AUTH-014). Tam UA saklanmaz.
 */
final class UserAgentSummary
{
    /** @var array<string, string> Sıra önemli: Edge/Opera Chrome içerir, Chrome Safari içerir. */
    private const BROWSERS = [
        'Edg/' => 'Edge', 'OPR/' => 'Opera', 'SamsungBrowser' => 'Samsung Internet', 'Firefox/' => 'Firefox',
        'Chrome/' => 'Chrome', 'Safari/' => 'Safari',
    ];

    /** @var array<string, string> */
    private const PLATFORMS = [
        'Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iPadOS',
        'Mac OS X' => 'macOS', 'Linux' => 'Linux',
    ];

    public static function from(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        $browser = self::match($userAgent, self::BROWSERS) ?? 'Tarayıcı';
        $platform = self::match($userAgent, self::PLATFORMS) ?? 'Bilinmeyen sistem';

        return $browser.' · '.$platform;
    }

    /** @param array<string, string> $map */
    private static function match(string $haystack, array $map): ?string
    {
        foreach ($map as $needle => $label) {
            if (str_contains($haystack, $needle)) {
                return $label;
            }
        }

        return null;
    }
}
