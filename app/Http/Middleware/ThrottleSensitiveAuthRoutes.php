<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify'ın kendi limiter'ı olmayan parola sıfırlama uçlarına SRS §5.4 limiti (3 / 10 dk, e-posta + IP).
 * Fortify rotaları paket tarafından kaydedildiği için rota adına göre uygulanır.
 */
final class ThrottleSensitiveAuthRoutes
{
    private const ROUTES = ['password.email', 'password.update'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $request->routeIs(...self::ROUTES)) {
            $key = 'password-reset:'.mb_strtolower((string) $request->input('email')).'|'.$request->ip();

            if (RateLimiter::tooManyAttempts($key, 3)) {
                throw new ThrottleRequestsException(__('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]));
            }

            RateLimiter::hit($key, 600);
        }

        return $next($request);
    }
}
