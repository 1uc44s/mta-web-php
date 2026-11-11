<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Kullanıcının etkin yetki özeti (docs/ADMIN.md §1, §5.2). Süresi dolmuş rol/branş üyelikleri sayılmaz.
 * Sonuç 10 dk cache'lenir (yalnızca dizi — Laravel 13 cache nesne unserialize etmez). Rol/branş değişince flush().
 */
final class PermissionResolver
{
    private const TTL = 600;

    /**
     * @return array{roles: list<string>, priority: int, is_staff: bool, is_super: bool, permissions: list<string>, branches: list<int>, lead_branches: list<int>}
     */
    public function for(User $user): array
    {
        /** @var array{roles: list<string>, priority: int, is_staff: bool, is_super: bool, permissions: list<string>, branches: list<int>, lead_branches: list<int>} */
        return Cache::remember(self::key($user->id), self::TTL, fn (): array => $this->compute($user->id));
    }

    public function flush(int $userId): void
    {
        Cache::forget(self::key($userId));
    }

    private static function key(int $userId): string
    {
        return 'perm:v1:'.$userId;
    }

    /**
     * @return array{roles: list<string>, priority: int, is_staff: bool, is_super: bool, permissions: list<string>, branches: list<int>, lead_branches: list<int>}
     */
    private function compute(int $userId): array
    {
        $now = now();

        $roles = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->where(fn ($q) => $q->whereNull('user_roles.expires_at')->orWhere('user_roles.expires_at', '>', $now))
            ->get(['roles.id', 'roles.slug', 'roles.priority', 'roles.is_staff']);

        $staffRoles = $roles->where('is_staff', true);

        $permissions = $roles->isEmpty() ? [] : DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->whereIn('role_permissions.role_id', $roles->pluck('id'))
            ->distinct()
            ->pluck('permissions.key')
            ->map(fn ($k): string => (string) $k)
            ->sort()->values()->all();

        $branches = DB::table('branch_members')
            ->join('branches', 'branches.id', '=', 'branch_members.branch_id')
            ->where('branch_members.user_id', $userId)
            ->where('branches.is_active', true)
            ->where(fn ($q) => $q->whereNull('branch_members.expires_at')->orWhere('branch_members.expires_at', '>', $now))
            ->get(['branch_members.branch_id', 'branch_members.is_lead']);

        return [
            'roles' => $roles->pluck('slug')->map(fn ($s): string => (string) $s)->values()->all(),
            'priority' => (int) ($staffRoles->max('priority') ?? 0),
            'is_staff' => $staffRoles->isNotEmpty(),
            'is_super' => $roles->contains('slug', Role::SUPER_ADMIN),
            'permissions' => $permissions,
            'branches' => $branches->pluck('branch_id')->map(fn ($id): int => (int) $id)->values()->all(),
            'lead_branches' => $branches->where('is_lead', true)->pluck('branch_id')->map(fn ($id): int => (int) $id)->values()->all(),
        ];
    }
}
