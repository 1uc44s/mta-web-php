<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ConfigValidator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Fortify Faz 3'te yapılandırılır (docs/ROADMAP.md 3.2). O zamana kadar rotaları kayıtlı değil.
        Fortify::ignoreRoutes();
    }

    public function boot(): void
    {
        // docs/RULES.md §3–4: değişmez tarihler, lazy loading ve sessiz atama yasağı (production hariç).
        Date::use(CarbonImmutable::class);
        Model::shouldBeStrict(! $this->app->isProduction());

        // Konsolda (ilk kurulum, key:generate) engellememek için yalnızca HTTP isteklerinde doğrulanır.
        if (! $this->app->runningInConsole()) {
            ConfigValidator::assertValid();
        }
    }
}
