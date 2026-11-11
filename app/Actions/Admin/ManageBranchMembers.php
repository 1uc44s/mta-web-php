<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\AuditAction;
use App\Models\Branch;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Branş üyeliği — docs/ADMIN.md §5.3 kuralları:
 * - branches.manage VEYA (branches.assign_members + bu branşın lideri)
 * - Üye olabilmek için personel rolü şart · lider atamak/çıkarmak yalnızca branches.manage
 * - Lider olmayan atayan, liderleri çıkaramaz.
 */
final class ManageBranchMembers
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function canManage(User $actor, Branch $branch): bool
    {
        return $actor->hasPermission('branches.manage')
            || ($actor->hasPermission('branches.assign_members') && $actor->leadsBranch($branch->id));
    }

    public function add(User $actor, Branch $branch, User $target, bool $isLead = false, ?CarbonImmutable $expiresAt = null): void
    {
        $this->guard($actor, $branch);

        if (! $target->isStaff()) {
            throw ValidationException::withMessages(['user' => __('admin.branches.errors.not_staff')]);
        }

        if ($isLead && ! $actor->hasPermission('branches.manage')) {
            throw new AuthorizationException;
        }

        if ($isLead && ! $actor->isSuperAdmin() && $target->rolePriority() >= $actor->rolePriority()) {
            throw ValidationException::withMessages(['user' => __('admin.staff.errors.hierarchy')]);
        }

        DB::table('branch_members')->updateOrInsert(
            ['branch_id' => $branch->id, 'user_id' => $target->id],
            ['is_lead' => $isLead, 'expires_at' => $expiresAt, 'assigned_by' => $actor->id, 'created_at' => now()],
        );

        $target->flushAuthz();
        $this->audit->record(AuditAction::BranchesMemberAdded, $branch, ['after' => ['user' => $target->ulid, 'is_lead' => $isLead]], null, $actor);
    }

    public function remove(User $actor, Branch $branch, User $target): void
    {
        $this->guard($actor, $branch);

        $member = DB::table('branch_members')->where(['branch_id' => $branch->id, 'user_id' => $target->id])->first();

        if ($member === null) {
            throw ValidationException::withMessages(['user' => __('admin.branches.errors.not_member')]);
        }

        if ((bool) $member->is_lead && ! $actor->hasPermission('branches.manage')) {
            throw new AuthorizationException;
        }

        DB::table('branch_members')->where('id', $member->id)->delete();

        $target->flushAuthz();
        $this->audit->record(AuditAction::BranchesMemberRemoved, $branch, ['before' => ['user' => $target->ulid, 'is_lead' => (bool) $member->is_lead]], null, $actor);
    }

    public function toggleLead(User $actor, Branch $branch, User $target): void
    {
        if (! $actor->hasPermission('branches.manage')) {
            throw new AuthorizationException;
        }

        $member = DB::table('branch_members')->where(['branch_id' => $branch->id, 'user_id' => $target->id])->first();

        if ($member === null) {
            throw ValidationException::withMessages(['user' => __('admin.branches.errors.not_member')]);
        }

        DB::table('branch_members')->where('id', $member->id)->update(['is_lead' => ! $member->is_lead]);

        $target->flushAuthz();
        $this->audit->record(AuditAction::BranchesLeadChanged, $branch, ['after' => ['user' => $target->ulid, 'is_lead' => ! $member->is_lead]], null, $actor);
    }

    private function guard(User $actor, Branch $branch): void
    {
        if (! $this->canManage($actor, $branch)) {
            throw new AuthorizationException;
        }
    }
}
