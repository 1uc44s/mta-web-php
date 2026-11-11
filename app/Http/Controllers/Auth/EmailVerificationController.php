<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\SecurityEventLogger;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** E-posta doğrulama — docs/SRS.md FR-AUTH-005/006, docs/ROUTES.md §4 (oturum gerektirmez, D-15). */
final class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()?->hasVerifiedEmail()
            ? to_route('panel.dashboard')
            : view('auth.verify-email');
    }

    public function verify(Request $request, SecurityEventLogger $security, string $ulid, string $hash): RedirectResponse
    {
        $user = User::query()->where('ulid', $ulid)->first();

        // İmza geçerli olsa bile e-posta değişmişse bağlantı geçersizdir.
        abort_if($user === null || ! hash_equals(hash('sha256', $user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            $security->record($user, 'email_verified', $request);
        }

        return $request->user()?->is($user)
            ? to_route('panel.dashboard')->with('toast', __('auth.verified'))
            : to_route('login')->with('status', __('auth.verified_login'));
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', __('auth.verification_sent'))->setStatusCode(303);
    }
}
