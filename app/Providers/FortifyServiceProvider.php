<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Auth\DisableTwoFactorForPlayers;
use App\Actions\Auth\ResetUserPassword;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;

/**
 * Fortify: giriş, çıkış, parola sıfırlama, 2FA, parola onayı (docs/ARCHITECTURE.md §5, docs/PRD.md D-06/D-15).
 */
final class FortifyServiceProvider extends ServiceProvider
{
    private static ?string $timingHash = null;

    public function register(): void
    {
        // Çıkışta tarayıcı cache'i temizlenir (docs/ARCHITECTURE.md §6, §8.4).
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse
        {
            public function toResponse($request): RedirectResponse
            {
                return redirect()->route('public.home')->with('toast', __('auth.logged_out'))
                    ->withHeaders(['Clear-Site-Data' => '"cache"']);
            }
        });

        // SEC-005: bilinmeyen e-posta / throttle durumunda da başarı ile aynı genel yanıt (hesap varlığı ifşa edilmez).
        $this->app->bind(
            FailedPasswordResetLinkRequestResponse::class,
            fn (): SuccessfulPasswordResetLinkRequestResponse => new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT),
        );

        // FR-AUTH-017: personel 2FA'yı kapatamaz.
        $this->app->bind(DisableTwoFactorAuthentication::class, DisableTwoFactorForPlayers::class);
    }

    public function boot(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));

        Fortify::authenticateUsing(fn (Request $request): ?User => $this->authenticate($request));

        // Parola onayı (password.confirm): giriş alanı `login` sanal bir alan olduğundan Fortify'ın varsayılan
        // `guard->validate([login => $user->login])` yolu kullanılamaz; doğrudan hash karşılaştırılır.
        Fortify::confirmPasswordsUsing(fn (User $user, ?string $password): bool => is_string($password) && $password !== '' && Hash::check($password, $user->password));
    }

    /**
     * docs/SRS.md FR-AUTH-007/011: e-posta veya kullanıcı adı ile giriş; askıdaki hesap gerekçesiyle reddedilir.
     */
    private function authenticate(Request $request): ?User
    {
        $login = trim((string) $request->input(Fortify::username()));
        $password = (string) $request->input('password');

        $user = str_contains($login, '@')
            ? User::query()->where('email', mb_strtolower($login))->first()
            : User::query()->where('username', $login)->first();

        if ($user === null) {
            // Var olmayan kullanıcıda da hash doğrulaması: yanıt süresi hesap varlığını ele vermez (SEC-005).
            Hash::check($password, self::$timingHash ??= Hash::make(Str::random(32)));

            return null;
        }

        if (! Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->suspensionHasExpired()) {
            $user->forceFill(['status' => UserStatus::Active, 'suspended_until' => null, 'suspension_reason' => null])->save();
        } elseif ($user->isSuspended()) {
            throw ValidationException::withMessages([Fortify::username() => $this->suspensionMessage($user)]);
        } elseif (! $user->status->canLogin()) {
            return null;
        }

        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => $password])->save();
        }

        return $user;
    }

    private function suspensionMessage(User $user): string
    {
        $reason = $user->suspension_reason ?? '—';

        return $user->suspended_until === null
            ? __('auth.suspended', ['reason' => $reason])
            : __('auth.suspended_until', [
                'reason' => $reason,
                'date' => $user->suspended_until->timezone(config('roleplay.display_timezone'))->locale('tr')->translatedFormat('d F Y H:i'),
            ]);
    }
}
