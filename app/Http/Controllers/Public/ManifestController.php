<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Brand;
use Illuminate\Http\JsonResponse;

/** PWA manifesti marka ayarlarından — docs/ROUTES.md (pwa.manifest), SRS FR-PWA-001. */
final class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $icons = [
            ['src' => Brand::iconUrl(192), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => Brand::iconUrl(512), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ];
        if (! Brand::hasCustomIcon()) {
            $icons[] = ['src' => asset('icons/maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'];
        }

        return response()->json([
            'name' => Brand::name(),
            'short_name' => Brand::shortName(),
            'description' => Brand::tagline() ?? Brand::name(),
            'lang' => 'tr',
            'dir' => 'ltr',
            'start_url' => '/panel?source=pwa',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#0e0b10',
            'theme_color' => '#0e0b10',
            'icons' => $icons,
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=3600'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
