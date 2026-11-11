<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CSRF token'ına ek katman: durum değiştiren isteklerde cross-site kaynakları reddeder (docs/RULES.md §7).
 * Fetch Metadata (Sec-Fetch-Site) varsa onu, yoksa Origin başlığını kullanır.
 * İkisi de yoksa (eski istemci, CLI) karar CSRF token kontrolüne bırakılır.
 */
final class VerifyOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $site = $request->headers->get('Sec-Fetch-Site');

        if ($site !== null) {
            abort_unless(in_array($site, ['same-origin', 'none'], true), 419);

            return $next($request);
        }

        $origin = $request->headers->get('Origin');

        if ($origin !== null) {
            abort_unless($origin === $request->getSchemeAndHttpHost(), 419);
        }

        return $next($request);
    }
}
