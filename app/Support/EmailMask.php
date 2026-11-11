<?php

declare(strict_types=1);

namespace App\Support;

/**
 * PII maskeleme: `fazil@gmail.com` → `f***@g***.com` (docs/DATABASE.md §10.18 recipient_masked, SRS FR-ADM-005).
 */
final class EmailMask
{
    public static function mask(?string $email): string
    {
        if ($email === null || ! str_contains($email, '@')) {
            return '—';
        }

        [$local, $domain] = explode('@', $email, 2);
        $dot = strrpos($domain, '.');
        $host = $dot === false ? $domain : substr($domain, 0, $dot);
        $tld = $dot === false ? '' : substr($domain, $dot);

        return mb_substr($local, 0, 1).'***@'.mb_substr($host, 0, 1).'***'.$tld;
    }
}
