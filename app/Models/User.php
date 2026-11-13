<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\Concerns\HasPublicUlid;
use App\Services\Authorization\PermissionResolver;
use App\Services\Messaging\MessageDispatcher;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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

    /**
     * Fortify, QR etiketinde giriş alanının (`login`) model niteliğini okur; bizde böyle bir kolon yok.
     * Etiket olarak kullanıcı adı kullanılır (e-posta authenticator uygulamasına yazılmaz).
     */
    public function twoFactorQrCodeUrl(): string
    {
        return app(TwoFactorAuthenticationProvider::class)->qrCodeUrl(
            (string) config('app.name'),
            $this->username,
            Fortify::currentEncrypter()->decrypt((string) $this->two_factor_secret),
        );
    }

    // ---- Yetki (docs/ADMIN.md §1, §5) ----------------------------------------

    /** @var array{roles: list<string>, priority: int, is_staff: bool, is_super: bool, permissions: list<string>, branches: list<int>, lead_branches: list<int>}|null */
    private ?array $authzCache = null;

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withPivot(['id', 'assigned_by', 'expires_at', 'created_at']);
    }

    /** @return BelongsToMany<Branch, $this> */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_members')->withPivot(['id', 'is_lead', 'assigned_by', 'expires_at', 'created_at']);
    }

    /** @return array{roles: list<string>, priority: int, is_staff: bool, is_super: bool, permissions: list<string>, branches: list<int>, lead_branches: list<int>} */
    public function authz(): array
    {
        return $this->authzCache ??= app(PermissionResolver::class)->for($this);
    }

    /** Rol/branş değişikliğinden sonra çağrılır (bellek + cache). */
    public function flushAuthz(): void
    {
        $this->authzCache = null;
        app(PermissionResolver::class)->flush($this->id);
    }

    public function isSuperAdmin(): bool
    {
        return $this->authz()['is_super'];
    }

    /** En az bir personel rolü (is_staff) var mı? Kozmetik roller /admin açmaz. */
    public function isStaff(): bool
    {
        return $this->authz()['is_staff'];
    }

    public function hasPermission(string $key): bool
    {
        return $this->isSuperAdmin() || in_array($key, $this->authz()['permissions'], true);
    }

    /**
     * İzin kapsamsız mı? (ADMIN §3: 🔸 izinler branş/eylem öneki ile sınırlıdır.) Süper admin ve config dışı
     * (ACP'de oluşturulmuş) roller kapsamsız sayılır.
     */
    public function hasUnscopedPermission(string $key): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->authz()['roles'] as $slug) {
            $meta = config('roles.'.$slug);
            if (! is_array($meta)) {
                if ($this->roles()->where('slug', $slug)->whereHas('permissions', fn ($q) => $q->where('key', $key))->exists()) {
                    return true;
                }

                continue;
            }
            if (in_array($key, $meta['permissions'], true) && ! in_array($key, $meta['scoped'], true)) {
                return true;
            }
        }

        return false;
    }

    public function rolePriority(): int
    {
        return $this->authz()['priority'];
    }

    /** @return list<int> */
    public function activeBranchIds(): array
    {
        return $this->authz()['branches'];
    }

    public function leadsBranch(int $branchId): bool
    {
        return in_array($branchId, $this->authz()['lead_branches'], true);
    }

    /** Hiyerarşi: hedef personelden kesin olarak yüksek öncelik (docs/ADMIN.md §4.2, ROUTES §6.2). */
    public function outranks(self $other): bool
    {
        // Süper admin en üst seviyedir (diğer süper adminler dahil); son süper admin koruması işlem katmanında.
        return $this->isSuperAdmin() || $this->rolePriority() > $other->rolePriority();
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

    /** Yayın bildirimi kanalı — routes/channels.php `user.{id}` (docs/ROUTES.md §7.4). */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'user.'.$this->id;
    }
}
