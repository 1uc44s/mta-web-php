<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Settings\SettingsRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Özellik bayrağı — docs/ROUTES.md `F:{key}` işareti, ADMIN §7 (`features.*` ayarları). Kapalı özellik 404 döner
 * (varlığı ifşa edilmez). Kullanım: `->middleware('feature:applications')`.
 */
final class EnsureFeatureEnabled
{
    public function __construct(private readonly SettingsRepository $settings) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless($this->settings->bool('features.'.$feature), 404);

        return $next($request);
    }
}
