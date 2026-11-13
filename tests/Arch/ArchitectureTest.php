<?php

declare(strict_types=1);

/*
 * Mimari kurallar — docs/ARCHITECTURE.md §3.1 ve docs/RULES.md §3–6.
 */

arch('PHP preseti: hata ayıklama ve eski fonksiyonlar yok')->preset()->php();

arch('güvenlik preseti: eval, exec, zayıf rastgelelik ve zayıf hash yok')->preset()->security();

arch('uygulama kodu strict types kullanır')
    ->expect('App')
    ->toUseStrictTypes();

arch('env() yalnızca config dosyalarında kullanılır')
    ->expect('env')
    ->not->toBeUsedIn('App');

arch('destek sınıfları ve middleware final')
    ->expect(['App\Support', 'App\Http\Middleware', 'App\Providers'])
    ->classes()
    ->toBeFinal();

arch('controller içinde DB facade kullanılmaz (iş mantığı Action/Service katmanında)')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

it('FR-WAL-002: wallets tablosuna yalnızca LedgerService yazar', function (): void {
    $offenders = [];
    $root = dirname(__DIR__, 2);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.DIRECTORY_SEPARATOR.'app'));

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php' || str_ends_with($file->getPathname(), 'LedgerService.php')) {
            continue;
        }
        $code = (string) file_get_contents($file->getPathname());
        // Ham sorguyla cüzdan güncellemesi veya modelde balance/version ataması yasak (WalletService yalnızca oluşturur).
        if (preg_match("/table\\(\\s*'wallets'\\s*\\)[^;]*->(update|increment|decrement|upsert)\\(/s", $code) === 1
            || preg_match("/(forceFill|update|fill)\\(\\s*\\[[^\\]]*'balance'\\s*=>/s", $code) === 1) {
            $offenders[] = str_replace($root.DIRECTORY_SEPARATOR, '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([]);
});

arch('modeller HTTP katmanına bağımlı değildir')
    ->expect('App\Models')
    ->not->toUse(['Illuminate\Http\Request', 'Illuminate\Support\Facades\Request']);
