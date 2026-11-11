<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Actions\Account\AccountDeletion;
use App\Actions\Account\AccountSecurity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\EmailChangeRequest;
use App\Http\Requests\Panel\PasswordUpdateRequest;
use App\Models\User;
use App\Models\UserSecurityEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Güvenlik ve hesap — docs/ROUTES.md §5.8, SRS FR-AUTH-014..020. */
final class SecurityController extends Controller
{
    public function __construct(private readonly AccountSecurity $security, private readonly AccountDeletion $deletion) {}

    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('panel.security', [
            'user' => $user,
            'sessions' => $this->security->sessions($user, $request->session()->getId()),
            'pendingEmail' => $this->security->pendingEmail($user),
        ]);
    }

    public function password(PasswordUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->security->changePassword($user, (string) $request->validated('password'), $request);

        return back()->with('toast', 'Parolan değiştirildi; diğer cihazlardaki oturumların kapatıldı.');
    }

    public function email(EmailChangeRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->security->requestEmailChange($user, (string) $request->validated('email'), $request);

        return back()->with('toast', 'Yeni adrese bir onay bağlantısı gönderdik (60 dk geçerli).');
    }

    public function emailConfirm(string $token): View
    {
        return view('panel.email-confirm', ['token' => $token, 'change' => $this->security->findEmailChange($token)]);
    }

    public function emailConfirmStore(Request $request, string $token): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->security->confirmEmailChange($user, $token, $request);

        return to_route('panel.security')->with('toast', 'E-posta adresin güncellendi.');
    }

    public function revokeSession(Request $request, string $session): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_if($this->security->revokeSessions($user, $request, 'one', $session) === 0, 404);

        return back()->with('toast', 'Oturum kapatıldı.');
    }

    public function revokeOthers(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->security->revokeSessions($user, $request, 'others');

        return back()->with('toast', 'Diğer tüm oturumlar kapatıldı.');
    }

    public function revokeAll(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->security->revokeSessions($user, $request, 'all');
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('toast', 'Tüm oturumların kapatıldı.');
    }

    public function events(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('panel.security-events', [
            'events' => UserSecurityEvent::query()->where('user_id', $user->id)->where('created_at', '>=', now()->subDays(90))->latest('id')->paginate(30),
        ]);
    }

    public function deleteRequest(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->deletion->request($user, $request);

        return back()->with('toast', 'Hesap silme talebin alındı. 14 gün içinde iptal edebilirsin.');
    }

    public function deleteCancel(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->deletion->cancel($user, $request);

        return back()->with('toast', 'Hesap silme talebin iptal edildi.');
    }
}
