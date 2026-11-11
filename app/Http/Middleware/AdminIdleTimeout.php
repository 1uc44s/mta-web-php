<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * docs/SRS.md FR-ADM-003: admin alanında 30 dk hareketsizlikten sonra parola yeniden onaylanır.
 * Son admin isteği zamanı oturumda tutulur; süre aşılırsa parola onayı sıfırlanır ve password.confirm'e gidilir.
 */
final class AdminIdleTimeout
{
    public const IDLE_SECONDS = 1800;

    private const KEY = 'admin.last_seen';

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $last = (int) $session->get(self::KEY, 0);

        if ($last > 0 && time() - $last > self::IDLE_SECONDS) {
            $session->forget([self::KEY, 'auth.password_confirmed_at']);
            $session->put('url.intended', $request->fullUrl());

            return redirect()->route('password.confirm')->with('toast', __('admin.idle_timeout'));
        }

        $session->put(self::KEY, time());

        return $next($request);
    }
}
