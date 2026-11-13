<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\AuthEventSubscriber;
use App\Models\User;
use App\Services\Bridge\ServerStatusResolver;
use App\Services\Content\MarkdownRenderer;
use App\Services\Push\PushTransport;
use App\Services\Push\WebPushTransport;
use App\Services\Settings\SettingsRepository;
use App\Support\ConfigValidator;
use App\View\Composers\AdminLayoutComposer;
use App\View\Composers\PanelLayoutComposer;
use App\View\Composers\PublicLayoutComposer;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class);
        $this->app->singleton(MarkdownRenderer::class);
        $this->app->singleton(ServerStatusResolver::class);
        $this->app->bind(PushTransport::class, WebPushTransport::class);
    }

    public function boot(): void
    {
        // docs/RULES.md §3–4: değişmez tarihler, lazy loading ve sessiz atama yasağı (production hariç).
        Date::use(CarbonImmutable::class);
        Model::shouldBeStrict(! $this->app->isProduction());

        Paginator::defaultView('components.ui.pagination');

        View::composer('components.layouts.public', PublicLayoutComposer::class);
        View::composer('components.layouts.admin', AdminLayoutComposer::class);
        View::composer('components.layouts.panel', PanelLayoutComposer::class);

        Event::subscribe(AuthEventSubscriber::class);

        // docs/ADMIN.md §1, §4.1: süper admin her şeyi yapar; katalogdaki izinler rol eşlemesinden çözülür.
        // Katalog dışı yetenekler (policy'ler) için null döner → normal Gate akışı.
        $catalog = array_fill_keys(array_keys((array) config('permissions')), true);
        Gate::before(function (mixed $user, string $ability) use ($catalog): ?bool {
            if (! $user instanceof User) {
                return null;
            }
            if ($user->isSuperAdmin()) {
                return true;
            }

            return isset($catalog[$ability]) && $user->hasPermission($ability) ? true : null;
        });

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
        // docs/SRS.md FR-BRG-006: anahtar başına 120/dk, heartbeat ayrı 6/dk.
        RateLimiter::for('bridge', fn (Request $request): Limit => Limit::perMinute(120)->by('bridge:'.substr((string) $request->header('X-Bridge-Key', $request->ip()), 0, 40)));
        RateLimiter::for('bridge-heartbeat', fn (Request $request): Limit => Limit::perMinute(6)->by('bridge-hb:'.substr((string) $request->header('X-Bridge-Key', $request->ip()), 0, 40)));
        RateLimiter::for('link-code', fn (Request $request): Limit => Limit::perHour(5)->by('link-code:'.$request->user()?->getAuthIdentifier()));
        // ROUTES §7.3: webhook uçları IP başına 300/dk (imza doğrulaması ayrıca).
        RateLimiter::for('webhooks', fn (Request $request): Limit => Limit::perMinute(300)->by('hook:'.$request->ip()));
        RateLimiter::for('public-api', fn (Request $request): Limit => Limit::perMinute(60)->by('papi:'.$request->ip()));
        RateLimiter::for('preferences', fn (Request $request): Limit => Limit::perMinute(30)->by('pref:'.$request->ip()));
        RateLimiter::for('poll', fn (Request $request): Limit => Limit::perMinute(30)->by('poll:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));

        // Kimlik doğrulama (FR-AUTH-008, SRS §5.4)
        RateLimiter::for('login', function (Request $request): array {
            $identifier = mb_strtolower((string) $request->input(Fortify::username()));

            return [
                Limit::perMinute(5)->by('login:'.$identifier.'|'.$request->ip()),
                Limit::perHour(30)->by('login-ip:'.$request->ip()),
            ];
        });
        RateLimiter::for('two-factor', fn (Request $request): Limit => Limit::perMinute(5)->by('2fa:'.$request->session()->get('login.id', $request->ip())));
        RateLimiter::for('register', fn (Request $request): Limit => Limit::perHour(5)->by('register:'.$request->ip()));
        RateLimiter::for('verification', fn (Request $request): Limit => Limit::perHour(6)->by('verify:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
        // docs/ROUTES.md T:uploads — dosya yükleme (medya, ileride ticket ekleri).
        RateLimiter::for('uploads', fn (Request $request): Limit => Limit::perMinute(20)->by('uploads:'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
        // docs/ROUTES.md §5.4: destek talebi oluşturma 5/saat, mesaj 20/10 dk (kullanıcı başına).
        RateLimiter::for('support-create', fn (Request $request): Limit => Limit::perHour(5)->by('sup-c:'.$request->user()?->getAuthIdentifier()));
        RateLimiter::for('support-message', fn (Request $request): Limit => Limit::perMinutes(10, 20)->by('sup-m:'.$request->user()?->getAuthIdentifier()));
    }
}
