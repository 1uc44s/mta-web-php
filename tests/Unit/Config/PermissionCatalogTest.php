<?php

declare(strict_types=1);

/*
 * docs/RULES.md §1.4: config/permissions.php ile docs/ADMIN.md §3 birebir aynı olmalıdır.
 */

function adminDocPermissionKeys(): array
{
    $doc = (string) file_get_contents(dirname(__DIR__, 3).'/docs/ADMIN.md');
    $section = explode('## 4. Süper Admin', explode('## 3. İzin kataloğu', $doc)[1])[0];

    preg_match_all('/^\| `([a-z_]+\.[a-z_.]+)`/m', $section, $matches);

    return $matches[1];
}

it('RULES-1: izin kataloğu ADMIN.md §3 ile birebir eşleşir', function (): void {
    $docKeys = adminDocPermissionKeys();
    $configKeys = array_keys(config('permissions'));

    expect($docKeys)->not->toBeEmpty()
        ->and($configKeys)->toEqualCanonicalizing($docKeys)
        ->and($configKeys)->toHaveCount(count(array_unique($configKeys)));
});

it('RULES-1: her izin bir grup, açıklama ve hassasiyet bayrağı taşır', function (): void {
    foreach (config('permissions') as $key => $meta) {
        expect($meta)->toHaveKeys(['group', 'description', 'sensitive'])
            ->and($meta['description'])->not->toBeEmpty("{$key} açıklaması boş")
            ->and($meta['sensitive'])->toBeBool();
    }
});

it('ARCH-7.5: köprü komut allowlist izinleri katalogda tanımlıdır', function (): void {
    $catalog = array_keys(config('permissions'));

    foreach (array_filter(config('roleplay.bridge.commands')) as $command => $permission) {
        expect($catalog)->toContain($permission);
    }
});
