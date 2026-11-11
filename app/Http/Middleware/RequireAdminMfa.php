<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * docs/SRS.md FR-AUTH-017: 2FA'sı onaylanmamış personel /admin'e giremez → kurulum sayfasına yönlendirilir.
 */
final class RequireAdminMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('panel.security.two-factor')->with('toast', __('admin.mfa_required'));
        }

        return $next($request);
    }
}
