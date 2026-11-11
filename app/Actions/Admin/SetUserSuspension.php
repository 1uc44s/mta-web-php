<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\AuditAction;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hesap askıya alma / açma — docs/SRS.md FR-ADM-006, ROUTES §6.2 (hiyerarşi), ADMIN §4.2 (son süper admin askıya alınamaz).
 */
final class SetUserSuspension
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(User $actor, User $target, bool $suspend, string $reason, ?CarbonImmutable $until = null): void
    {
        if ($actor->id === $target->id) {
            throw ValidationException::withMessages(['status' => __('admin.users.errors.self')]);
        }

        if ($target->isStaff() && ! $actor->outranks($target)) {
            throw ValidationException::withMessages(['status' => __('admin.staff.errors.hierarchy')]);
        }

        $before = ['status' => $target->status->value, 'suspended_until' => $target->suspended_until?->toIso8601String()];

        DB::transaction(function () use ($target, $suspend, $reason, $until): void {
            if ($suspend && $target->isSuperAdmin()) {
                $supers = DB::table('user_roles')->join('roles', 'roles.id', '=', 'user_roles.role_id')
                    ->join('users', 'users.id', '=', 'user_roles.user_id')
                    ->where('roles.slug', Role::SUPER_ADMIN)->where('users.status', UserStatus::Active->value)
                    ->lockForUpdate()->count();

                if ($supers <= 1) {
                    throw ValidationException::withMessages(['status' => __('admin.staff.errors.last_super_admin')]);
                }
            }

            $target->forceFill($suspend
                ? ['status' => UserStatus::Suspended, 'suspended_until' => $until, 'suspension_reason' => mb_substr($reason, 0, 500)]
                : ['status' => UserStatus::Active, 'suspended_until' => null, 'suspension_reason' => null])->save();

            if ($suspend) {
                DB::table('sessions')->where('user_id', $target->id)->delete();
            }
        });

        $this->audit->record(
            $suspend ? AuditAction::UsersSuspended : AuditAction::UsersUnsuspended,
            $target,
            ['before' => $before, 'after' => ['status' => $target->status->value, 'suspended_until' => $until?->toIso8601String()]],
            $reason,
            $actor,
        );
    }
}
