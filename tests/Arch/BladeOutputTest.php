<?php

declare(strict_types=1);

/*
 * docs/RULES.md §6: `{!! !!}` yalnızca SafeHtml değerleri için ve yalnızca izin listesindeki dosyalarda kullanılabilir.
 * Yeni bir kullanım eklemek için dosyayı aşağıdaki listeye gerekçesiyle ekle.
 */

const RAW_OUTPUT_ALLOWLIST = [
    // 'resources/views/components/ui/prose.blade.php', // örnek: SafeHtml render eden bileşen
];

it('RULES-6: ham Blade çıktısı yalnızca izin listesindeki dosyalarda bulunur', function (): void {
    $root = dirname(__DIR__, 2);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/views'));
    $offenders = [];

    foreach ($files as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

        if (! in_array($relative, RAW_OUTPUT_ALLOWLIST, true) && str_contains((string) file_get_contents($file->getPathname()), '{!!')) {
            $offenders[] = $relative;
        }
    }

    expect($offenders)->toBe([]);
});

it('SEC-024: view dosyalarında satır içi style ve olay işleyici attribute yoktur', function (): void {
    $root = dirname(__DIR__, 2);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/views'));
    $offenders = [];

    foreach ($files as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        if (preg_match('/\s(style|on[a-z]+)\s*=\s*["\']/i', $contents) === 1) {
            $offenders[] = $file->getFilename();
        }
    }

    expect($offenders)->toBe([]);
});
