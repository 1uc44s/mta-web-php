<?php

declare(strict_types=1);

/*
 * docs/DESIGN.md §3.1 kontrast tablosu — token değerleri resources/css/app.css'ten okunur; eşik altı çift testi düşürür.
 * Token değiştirilirse eşik değil token ayarlanır.
 */

/** @return array<string, string> tema bloğundaki `--c-*` hex değerleri */
function themeTokens(string $selector): array
{
    $css = (string) file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');
    $start = strpos($css, $selector);
    expect($start)->not->toBeFalse();
    $block = substr($css, (int) $start, (int) strpos($css, '}', (int) $start) - (int) $start);
    preg_match_all('/--c-([a-z0-9-]+):\s*(#[0-9a-f]{6});/i', $block, $m, PREG_SET_ORDER);

    return array_column($m, 2, 1);
}

function luminance(string $hex): float
{
    $channels = array_map(function (string $pair): float {
        $v = hexdec($pair) / 255;

        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    }, str_split(ltrim($hex, '#'), 2));

    return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
}

function contrast(string $a, string $b): float
{
    [$hi, $lo] = [max(luminance($a), luminance($b)), min(luminance($a), luminance($b))];

    return ($hi + 0.05) / ($lo + 0.05);
}

it('tema token çiftleri WCAG eşiklerini sağlar', function (string $selector): void {
    $t = themeTokens($selector);

    $pairs = [
        ['text', 'bg', 4.5], ['text-muted', 'surface-1', 4.5], ['text-subtle', 'surface-1', 4.5], ['text-subtle', 'surface-3', 4.5],
        ['accent', 'bg', 4.5], ['accent', 'surface-1', 4.5], ['accent-fg', 'accent', 4.5], ['amber', 'surface-1', 4.5],
        ['danger', 'surface-1', 4.5], ['success', 'surface-1', 4.5], ['info', 'surface-1', 4.5], ['border-input', 'surface-2', 3.0],
    ];

    foreach ($pairs as [$fg, $bg, $min]) {
        expect(contrast($t[$fg], $t[$bg]))->toBeGreaterThanOrEqual($min, "{$selector}: {$fg}/{$bg}");
    }
})->with([":root,\n[data-theme='dark']", "[data-theme='light'] {"]);
