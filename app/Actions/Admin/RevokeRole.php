<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\AuditAction;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Messaging\MessageDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rol kaldırma — docs/ADMIN.md §4.2 (son süper admin), §5.3 (son personel rolü → branşlar silinir, oturumlar kapanır).
 */
final class RevokeRole
{
    public function __construct(private readonly AuditLogger $audit, private readonly MessageDispatcher $messages) {}

    public function handle(User $actor, User $target, Role $role, string $reason): void
    {
        if ($role->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            throw ValidationException::withMessages(['role' => __('admin.staff.errors.super_only')]);
        }

        if (! $actor->isSuperAdmin() && ($role->priority >= $actor->rolePriority() || ($actor->id !== $target->id && ! $actor->outranks($target)))) {
            throw ValidationException::withMessages(['role' => __('admin.staff.errors.hierarchy')]);
        }

        $lostStaff = DB::transaction(function () use ($target, $role): bool {
            if ($role->isSuperAdmin()) {
                // Son süper admin koruması: sayım kilitli okunur (eşzamanlı iki kaldırma yarışını önler).
                $supers = DB::table('user_roles')->where('role_id', $role->id)
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->lockForUpdate()->count();

                if ($supers <= 1) {
                    throw ValidationException::withMessages(['role' => __('admin.staff.errors.last_super_admin')]);
                }
            }

            $deleted = DB::table('user_roles')->where(['user_id' => $target->id, 'role_id' => $role->id])->delete();

            if ($deleted === 0) {
                throw ValidationException::withMessages(['role' => __('admin.staff.errors.not_assigned')]);
            }

            $remainingStaff = DB::table('user_roles')->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->where('user_roles.user_id', $target->id)->where('roles.is_staff', true)->exists();

            if (! $remainingStaff) {
                DB::table('branch_members')->where('user_id', $target->id)->delete();
                DB::table('sessions')->where('user_id', $target->id)->delete();
            }

            return ! $remainingStaff;
        });

        $target->flushAuthz();

        $this->audit->record(
            $role->isSuperAdmin() ? AuditAction::SuperAdminRevoked : AuditAction::RolesRevoked,
            $target,
            ['before' => ['role' => $role->slug], 'after' => ['staff' => ! $lostStaff]],
            $reason,
            $actor,
        );

        $this->messages->send('staff.role_changed', $target, [
            'user.username' => $target->username, 'role.name' => $role->name, 'action' => 'kaldırıldı', 'link.admin' => route('panel.dashboard'),
        ]);
    }
}
