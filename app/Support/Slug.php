<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Türkçe slug üretimi — docs/CMS.md §3.1: ç→c, ğ→g, ı→i, İ→i, ö→o, ş→s, ü→u · biçim [a-z0-9-] · ≤ 120 · rezerv kelimeler yasak.
 */
final class Slug
{
    /** @var list<string> */
    public const RESERVED = ['create', 'edit', 'category', 'new', 'reorder', 'preview'];

    private const MAP = ['ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'I' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u'];

    public static function make(string $text): string
    {
        $text = mb_strtolower(strtr($text, self::MAP));
        $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim(mb_substr(trim($text, '-'), 0, 120), '-');

        if ($text === '' || in_array($text, self::RESERVED, true)) {
            $text = ($text === '' ? 'icerik' : $text).'-'.substr(bin2hex(random_bytes(3)), 0, 5);
        }

        return $text;
    }
}
