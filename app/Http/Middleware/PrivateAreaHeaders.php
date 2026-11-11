<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * docs/SRS.md FR-UCP-007, FR-PWA-003: kimliği doğrulanmış alanlar (panel/admin) indekslenmez ve hiçbir katmanda
 * cache'lenmez. Service worker da bu başlıkları ve yolları ağdan-yalnız (network-only) kabul eder.
 */
final class PrivateAreaHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
