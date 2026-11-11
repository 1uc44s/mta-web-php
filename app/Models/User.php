<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\Concerns\HasPublicUlid;
use App\Services\Messaging\MessageDispatcher;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Web hesabı — docs/DATABASE.md §3.1. Oyun hesabından ayrı kimliktir (docs/PRD.md §1.1).
 *
 * @property int $id
 * @property string $ulid
 * @property string $username
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property UserStatus $status
 * @property CarbonImmutable|null $suspended_until
 * @property string|null $suspension_reason
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property string $theme
 * @property bool $reduced_effects
 * @property CarbonImmutable|null $last_login_at
 * @property CarbonImmutable|null $deletion_requested_at
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasPublicUlid;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = ['username', 'email', 'password', 'theme', 'reduced_effects', 'locale'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected $attributes = ['status' => 'active', 'theme' => 'system', 'reduced_effects' => false, 'locale' => 'tr'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'suspended_until' => 'immutable_datetime',
            'two_factor_confirmed_at' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            'deletion_requested_at' => 'immutable_datetime',
            'reduced_effects' => 'boolean',
        ];
    }

    /** Askı süresi dolmamış mı? (Süresi dolan askı zamanlayıcı ve EnsureAccountActive ile kalkar.) */
    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended
            && ($this->suspended_until === null || $this->suspended_until->isFuture());
    }

    public function suspensionHasExpired(): bool
    {
        return $this->status === UserStatus::Suspended
            && $this->suspended_until !== null
            && $this->suspended_until->isPast();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret !== null;
    }

    /** Doğrulama bağlantısı: imzalı, oturum gerektirmez (docs/PRD.md D-15). */
    public function sendEmailVerificationNotification(): void
    {
        $minutes = (int) config('auth.verification.expire', 60);

        app(MessageDispatcher::class)->send('auth.verify_email', $this, [
            'user.username' => $this->username,
            'link.verify' => URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), [
                'ulid' => $this->ulid,
                'hash' => hash('sha256', $this->getEmailForVerification()),
            ]),
            'expires_minutes' => $minutes,
        ]);
    }

    /** @param  string  $token */
    public function sendPasswordResetNotification($token): void
    {
        app(MessageDispatcher::class)->send('auth.password_reset', $this, [
            'user.username' => $this->username,
            'link.reset' => route('password.reset', ['token' => $token, 'email' => $this->getEmailForPasswordReset()]),
            'expires_minutes' => (int) config('auth.passwords.users.expire', 60),
        ]);
    }
}
