<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Bridge\ServerStatusResolver;
use App\Services\Content\MarkdownRenderer;
use App\Services\Settings\SettingsRepository;
use App\Support\ConfigValidator;
use App\View\Composers\PublicLayoutComposer;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Fortify Faz 3'te yapılandırılır (docs/ROADMAP.md 3.2). O zamana kadar rotaları kayıtlı değil.
        Fortify::ignoreRoutes();

        $this->app->singleton(SettingsRepository::class);
        $this->app->singleton(MarkdownRenderer::class);
        $this->app->singleton(ServerStatusResolver::class);
    }

    public function boot(): void
    {
        // docs/RULES.md §3–4: değişmez tarihler, lazy loading ve sessiz atama yasağı (production hariç).
        Date::use(CarbonImmutable::class);
        Model::shouldBeStrict(! $this->app->isProduction());

        Paginator::defaultView('components.ui.pagination');

        View::composer('components.layouts.public', PublicLayoutComposer::class);

        $this->configureRateLimiting();

        // Konsolda (ilk kurulum, key:generate) engellememek için yalnızca HTTP isteklerinde doğrulanır.
        if (! $this->app->runningInConsole()) {
            ConfigValidator::assertValid();
        }
    }

    /** docs/SRS.md §5.4 rate limit tablosu. */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('contact', fn (Request $request): Limit => Limit::perHour(5)->by('contact:'.$request->ip()));
        RateLimiter::for('public-api', fn (Request $request): Limit => Limit::perMinute(60)->by('papi:'.$request->ip()));
        RateLimiter::for('preferences', fn (Request $request): Limit => Limit::perMinute(30)->by('pref:'.$request->ip()));
        RateLimiter::for('poll', fn (Request $request): Limit => Limit::perMinute(30)->by('poll:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }
}
