<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\Security\SecurityEventLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Fortify;

/**
 * Kimlik olaylarını güvenlik kaydına yazar (docs/SRS.md FR-AUTH-009/010, docs/DATABASE.md §3.8).
 */
final class AuthEventSubscriber
{
    public function __construct(private readonly SecurityEventLogger $security) {}

    public function onLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        $this->security->record($event->user, 'login_success', request(), ['remember' => $event->remember]);
    }

    public function onFailed(Failed $event): void
    {
        $identifier = (string) ($event->credentials[Fortify::username()] ?? $event->credentials['email'] ?? '');
        $user = $event->user instanceof User ? $event->user : null;

        $this->security->record($user, 'login_failed', request(), [], $identifier !== '' ? $identifier : null);
    }

    public function onLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->security->record($event->user, 'logout', request());
        }
    }

    public function onTwoFactorEnabled(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->security->record($event->user, 'two_factor_enabled', request());
    }

    public function onTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->security->record($event->user, 'two_factor_disabled', request());
    }

    public function onRecoveryCodeUsed(RecoveryCodeReplaced $event): void
    {
        $this->security->record($event->user instanceof User ? $event->user : null, 'recovery_code_used', request());
    }

    /** @return array<class-string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'onLogin',
            Failed::class => 'onFailed',
            Logout::class => 'onLogout',
            TwoFactorAuthenticationConfirmed::class => 'onTwoFactorEnabled',
            TwoFactorAuthenticationDisabled::class => 'onTwoFactorDisabled',
            RecoveryCodeReplaced::class => 'onRecoveryCodeUsed',
        ];
    }
}
