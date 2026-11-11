<?php

declare(strict_types=1);

namespace App\Support;

/**
 * IP verisinin KVKK uyumlu işlenmesi (docs/SRS.md SEC-043): HMAC (eşleştirme) + maske (gösterim).
 */
final class IpMasker
{
    public static function hash(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }

    /** 85.105.12.34 → 85.105.x.x · 2a02:e0:12:34::1 → 2a02:e0:12::/48 */
    public static function mask(?string $ip): ?string
    {
        if ($ip === null || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $parts = explode('.', $ip);

            return $parts[0].'.'.$parts[1].'.x.x';
        }

        $packed = (string) inet_pton($ip);
        $groups = array_values(unpack('n8', $packed) ?: []);

        return sprintf('%x:%x:%x::/48', $groups[0] ?? 0, $groups[1] ?? 0, $groups[2] ?? 0);
    }
}
