<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\AuditAction;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Messaging\MessageDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rol atama — docs/ADMIN.md §4.2, §6 (adım 2), ROUTES §6.10. Yetki (staff.manage) controller'da; burada iş kuralları.
 */
final class AssignRole
{
    public function __construct(private readonly AuditLogger $audit, private readonly MessageDispatcher $messages) {}

    public function handle(User $actor, User $target, Role $role, string $reason, ?CarbonImmutable $expiresAt = null, bool $breakGlass = false): void
    {
        if (! $breakGlass) {
            $this->authorize($actor, $target, $role);
        }

        if ($target->email_verified_at === null && $role->is_staff) {
            throw ValidationException::withMessages(['role' => __('admin.staff.errors.unverified')]);
        }

        DB::transaction(function () use ($actor, $target, $role, $expiresAt, $breakGlass): void {
            $where = ['user_id' => $target->id, 'role_id' => $role->id];

            if (DB::table('user_roles')->where($where)->lockForUpdate()->exists()) {
                DB::table('user_roles')->where($where)->update(['expires_at' => $expiresAt]);
            } else {
                DB::table('user_roles')->insert([...$where, 'expires_at' => $expiresAt, 'assigned_by' => $breakGlass ? null : $actor->id, 'created_at' => now()]);
            }
        });

        $target->flushAuthz();

        $action = match (true) {
            $breakGlass => AuditAction::SuperAdminBreakGlass,
            $role->isSuperAdmin() => AuditAction::SuperAdminAssigned,
            default => AuditAction::RolesAssigned,
        };

        $this->audit->record(
            $action, $target,
            ['after' => ['role' => $role->slug, 'expires_at' => $expiresAt?->toIso8601String()]],
            $reason,
            $breakGlass ? null : $actor,
            $breakGlass ? 'system' : 'user',
        );

        $this->messages->send('staff.role_changed', $target, [
            'user.username' => $target->username, 'role.name' => $role->name, 'action' => 'atandı', 'link.admin' => route('admin.dashboard'),
        ]);
    }

    private function authorize(User $actor, User $target, Role $role): void
    {
        if ($role->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            throw ValidationException::withMessages(['role' => __('admin.staff.errors.super_only')]);
        }

        if (! $actor->isSuperAdmin() && $role->priority >= $actor->rolePriority()) {
            throw ValidationException::withMessages(['role' => __('admin.staff.errors.hierarchy')]);
        }

        if ($actor->id !== $target->id && $target->isStaff() && ! $actor->outranks($target)) {
            throw ValidationException::withMessages(['role' => __('admin.staff.errors.hierarchy')]);
        }
    }
}
