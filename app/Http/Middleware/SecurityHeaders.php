<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Realtime;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nonce tabanlı CSP ve güvenlik başlıkları (docs/SRS.md SEC-024, SEC-025).
 * 'unsafe-inline' ve 'unsafe-eval' yalnızca yerel Vite HMR açıkken stil için eklenir.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('Content-Security-Policy', $this->contentSecurityPolicy($request, (string) Vite::cspNonce()));
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('X-Frame-Options', 'DENY');

        if ($request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(Request $request, string $nonce): string
    {
        $script = ["'self'", "'nonce-{$nonce}'"];
        $style = ["'self'", "'nonce-{$nonce}'"];
        $connect = ["'self'"];

        // Gerçek zaman (Faz 10): Reverb WebSocket host'u yalnızca etkinken eklenir.
        if (($realtime = Realtime::connectSource()) !== null) {
            $connect[] = $realtime;
        }

        // Yerel geliştirme: Vite dev sunucusu (HMR) farklı bir porttan çalışır.
        if (! app()->isProduction() && Vite::isRunningHot()) {
            $hot = rtrim(trim((string) file_get_contents(Vite::hotFile())), '/');
            $script[] = $hot;
            $style[] = $hot;
            $style[] = "'unsafe-inline'"; // Vite dev, CSS'i <style> etiketi olarak enjekte eder.
            $connect[] = $hot;
            $connect[] = (string) preg_replace('#^http#', 'ws', $hot);
        }

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => $script,
            'style-src' => $style,
            'img-src' => ["'self'", 'data:', 'https:'],
            'font-src' => ["'self'"],
            'connect-src' => $connect,
            'frame-src' => ['https://www.youtube-nocookie.com'],
            'worker-src' => ["'self'"],   // PWA service worker (FR-PWA-002)
            'manifest-src' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
        ];

        $policy = collect($directives)
            ->map(fn (array $sources, string $name): string => $name.' '.implode(' ', array_unique($sources)))
            ->implode('; ');

        if ($request->isSecure()) {
            $policy .= '; upgrade-insecure-requests';
        }

        return $policy;
    }
}
