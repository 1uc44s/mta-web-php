<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\AuditAction;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Audit\AuditLogger;
use App\Services\Authorization\PermissionResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Özel rol oluşturma / güncelleme / silme — docs/ROUTES.md §6.10. Sistem rolleri değiştirilemez (izinleri seed'den gelir).
 */
final class SaveRole
{
    public function __construct(private readonly AuditLogger $audit, private readonly PermissionResolver $resolver) {}

    /** @param  array<string, mixed>  $data  RoleRequest::validated() */
    public function create(array $data): Role
    {
        $role = DB::transaction(function () use ($data): Role {
            $role = Role::query()->create([...self::attributes($data), 'is_system' => false]);
            $this->syncPermissions($role, $data['permissions'] ?? []);

            return $role;
        });

        $this->audit->record(AuditAction::RolesCreated, $role, ['after' => ['slug' => $role->slug, 'permissions' => $data['permissions'] ?? []]], (string) $data['reason']);

        return $role;
    }

    /** @param  array<string, mixed>  $data */
    public function update(Role $role, array $data): void
    {
        self::guardSystem($role);
        $before = ['attributes' => $role->only(['name', 'slug', 'priority', 'is_staff']), 'permissions' => $role->permissions()->pluck('key')->all()];

        DB::transaction(function () use ($role, $data): void {
            $role->update(self::attributes($data));
            $this->syncPermissions($role, $data['permissions'] ?? []);
        });

        $role->users()->pluck('users.id')->each(fn ($id) => $this->resolver->flush((int) $id));

        $this->audit->record(AuditAction::RolesUpdated, $role, [
            'before' => $before,
            'after' => ['attributes' => $role->only(['name', 'slug', 'priority', 'is_staff']), 'permissions' => $data['permissions'] ?? []],
        ], (string) $data['reason']);
    }

    public function delete(Role $role, string $reason): void
    {
        self::guardSystem($role);

        if ($role->users()->exists()) {
            throw ValidationException::withMessages(['role' => __('admin.roles.errors.in_use')]);
        }

        $snapshot = ['slug' => $role->slug, 'name' => $role->name];
        $role->delete();
        $this->audit->record(AuditAction::RolesDeleted, null, ['before' => $snapshot], $reason);
    }

    private static function guardSystem(Role $role): void
    {
        if ($role->is_system) {
            throw ValidationException::withMessages(['role' => __('admin.roles.errors.system')]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function attributes(array $data): array
    {
        return [
            'name' => $data['name'], 'slug' => $data['slug'], 'description' => $data['description'] ?? null,
            'priority' => (int) $data['priority'], 'color' => $data['color'] ?? null, 'is_staff' => (bool) ($data['is_staff'] ?? false),
        ];
    }

    /** @param  list<string>  $keys */
    private function syncPermissions(Role $role, array $keys): void
    {
        $ids = Permission::query()->whereIn('key', $keys)->pluck('id')->all();
        $role->permissions()->syncWithPivotValues($ids, ['created_at' => now()]);
    }
}
