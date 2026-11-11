<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * docs/SRS.md FR-ADM-001: /admin yalnızca personel rolü (is_staff) olanlara açıktır.
 * Personel olmayanlara 404 döner: yönetim alanının varlığı ve rota yapısı ifşa edilmez.
 */
final class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->isStaff(), 404);

        return $next($request);
    }
}
