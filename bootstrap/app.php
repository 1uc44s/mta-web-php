<?php

declare(strict_types=1);

use App\Http\Middleware\AdminIdleTimeout;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\LogAdminActivity;
use App\Http\Middleware\PrivateAreaHeaders;
use App\Http\Middleware\RequireAdminMfa;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ThrottleSensitiveAuthRoutes;
use App\Http\Middleware\VerifyOrigin;
use App\Support\ApiErrorResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * Rota ve middleware yapısı: docs/ARCHITECTURE.md §5.3, docs/ROUTES.md §2.
 */

$wantsJson = static fn (Request $request): bool => $request->is('api/*', 'webhooks/*') || $request->expectsJson();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('panel')->prefix('panel')->name('panel.')
                ->group(base_path('routes/panel.php'));

            Route::middleware('admin')->prefix('admin')->name('admin.')
                ->group(base_path('routes/admin.php'));

            Route::middleware('hooks')->prefix('webhooks')->name('webhooks.')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global: eşleşmeyen rotalardaki hata sayfaları da kimlik ve güvenlik başlıklarını alır.
        $middleware->prepend(AssignRequestId::class);
        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [VerifyOrigin::class, ThrottleSensitiveAuthRoutes::class]);

        $middleware->alias([
            'active' => EnsureAccountActive::class,
            'staff' => EnsureStaff::class,
            'mfa' => RequireAdminMfa::class,
            'admin.idle' => AdminIdleTimeout::class,
            'admin.activity' => LogAdminActivity::class,
            'private.headers' => PrivateAreaHeaders::class,
        ]);

        // Girişli kullanıcı misafir sayfasına gelirse panele (Fortify home), misafir korumalı sayfaya gelirse girişe.
        $middleware->redirectUsersTo('/panel');
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Tema/efekt/çerez tercihi cookie'leri gizli değildir; değerleri allowlist ile doğrulanır (docs/DESIGN.md §4).
        $middleware->encryptCookies(except: ['mrp_theme', 'mrp_effects', 'mrp_consent']);

        // docs/SRS.md FR-ADM-001 zinciri. Rota bazında ayrıca `can:` (FR-ADM-002) ve kritik işlemlerde `password.confirm` (FR-ADM-004).
        // private.headers: FR-UCP-007 / FR-PWA-003 (noindex + no-store).
        $middleware->group('panel', ['web', 'auth', 'active', 'private.headers']);
        $middleware->group('admin', ['web', 'auth', 'verified', 'active', 'staff', 'mfa', 'admin.idle', 'admin.activity', 'private.headers']);
        $middleware->group('hooks', []);

        // CSRF istisnası yalnızca imza doğrulamalı webhook'lar (docs/RULES.md §7).
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
    })
    ->withExceptions(function (Exceptions $exceptions) use ($wantsJson): void {
        $exceptions->shouldRenderJsonWhen($wantsJson);

        // JSON uçlarında tek hata biçimi (docs/SRS.md §8). Web'de resources/views/errors/* kullanılır.
        $exceptions->render(function (Throwable $e, Request $request) use ($wantsJson) {
            return $wantsJson($request) ? ApiErrorResponse::fromThrowable($e) : null;
        });
    })->create();
