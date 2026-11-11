<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Actions\GameAccounts\ManageLinks;
use App\Enums\AuditAction;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Security\SecurityEventLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Hesap silme — docs/SRS.md FR-AUTH-020 (14 gün bekleme), DATABASE §13 (anonimleştirme).
 * Finansal kayıtlar ve audit değişmez; ticket mesajları korunur, yazar "Silinmiş kullanıcı" görünür.
 */
final class AccountDeletion
{
    public const GRACE_DAYS = 14;

    public function __construct(
        private readonly SecurityEventLogger $security,
        private readonly AuditLogger $audit,
        private readonly ManageLinks $links,
    ) {}

    public function request(User $user, Request $request): void
    {
        if ($user->isStaff()) {
            throw ValidationException::withMessages(['account' => 'Personel hesabı silinmeden önce rollerinin kaldırılması gerekir.']);
        }

        $user->forceFill(['status' => UserStatus::PendingDeletion, 'deletion_requested_at' => now()])->save();
        $this->security->record($user, 'deletion_requested', $request);
    }

    public function cancel(User $user, Request $request): void
    {
        if ($user->status !== UserStatus::PendingDeletion) {
            return;
        }

        $user->forceFill(['status' => UserStatus::Active, 'deletion_requested_at' => null])->save();
        $this->security->record($user, 'deletion_cancelled', $request);
    }

    /** DATABASE §13 — tek transaction. Son süper admin anonimleştirilemez (ADMIN §4.2). */
    public function anonymize(User $user, ?User $actor, string $reason): void
    {
        if ($user->isSuperAdmin() && User::query()->whereHas('roles', fn ($q) => $q->where('slug', Role::SUPER_ADMIN))->count() <= 1) {
            throw ValidationException::withMessages(['account' => __('admin.staff.errors.last_super_admin')]);
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'username' => 'silinmis_'.$user->id,
                'email' => 'deleted+'.$user->ulid.'@invalid.local',
                'password' => Hash::make(Str::random(48)),
                'remember_token' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'status' => UserStatus::Anonymized,
                'suspension_reason' => null,
            ])->save();

            foreach (['sessions', 'user_notification_preferences', 'email_change_requests', 'user_security_events', 'user_roles', 'branch_members', 'game_link_codes'] as $table) {
                DB::table($table)->where('user_id', $user->id)->delete();
            }
            DB::table('notifications')->where(['notifiable_type' => $user->getMorphClass(), 'notifiable_id' => $user->id])->delete();
            // DATABASE §13 adım 3: aktif oyun hesabı bağlantıları `revoked` olur, köprüye `account.link_revoked` gider.
            $this->links->revokeAllFor($user);
        });

        $user->flushAuthz();
        $this->audit->record(AuditAction::UsersAnonymized, $user, ['after' => ['status' => 'anonymized']], $reason, $actor, $actor === null ? 'scheduler' : 'user');
    }

    /** Zamanlayıcı: bekleme süresi dolan talepleri anonimleştirir. */
    public function processDue(): int
    {
        $count = 0;
        User::query()->where('status', UserStatus::PendingDeletion->value)
            ->where('deletion_requested_at', '<=', now()->subDays(self::GRACE_DAYS))
            ->each(function (User $user) use (&$count): void {
                $this->anonymize($user, null, 'Kullanıcı talebiyle hesap silme (14 gün bekleme sonrası)');
                $count++;
            });

        return $count;
    }
}
