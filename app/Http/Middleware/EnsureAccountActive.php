<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * docs/SRS.md FR-AUTH-011: askıya alınan/anonimleştirilen/pasif hesabın oturumu sonraki istekte kapatılır;
 * süresi dolan askı otomatik kalkar.
 */
final class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            if ($user->suspensionHasExpired()) {
                $user->forceFill(['status' => UserStatus::Active, 'suspended_until' => null, 'suspension_reason' => null])->save();
            } elseif ($user->isSuspended() || ! $user->status->canLogin()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['login' => __('auth.session_ended_suspended')]);
            }
        }

        return $next($request);
    }
}
