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

arch('modeller HTTP katmanına bağımlı değildir')
    ->expect('App\Models')
    ->not->toUse(['Illuminate\Http\Request', 'Illuminate\Support\Facades\Request']);
