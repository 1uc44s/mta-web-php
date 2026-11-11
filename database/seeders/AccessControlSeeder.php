<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * İzin kataloğu, sistem rolleri + matris ve seed branşları (docs/DATABASE.md §12, ADMIN §2–3, §5.1).
 * İdempotent: her çalıştırmada config ile senkronlar. Katalogda olmayan izinler silinir.
 * Sistem rollerinin izinleri config/roles.php'ye göre yeniden yazılır (ACP'den yapılan değişiklik sistem rollerinde kalıcı değildir).
 */
final class AccessControlSeeder extends Seeder
{
    private const BRANCHES = [
        ['slug' => 'yonetim', 'name' => 'Yönetim', 'type' => 'general', 'description' => 'Escalation hedefi'],
        ['slug' => 'destek-genel', 'name' => 'Destek – Genel', 'type' => 'support', 'parent' => 'yonetim'],
        ['slug' => 'destek-teknik', 'name' => 'Destek – Teknik', 'type' => 'support', 'parent' => 'yonetim'],
        ['slug' => 'moderasyon', 'name' => 'Moderasyon', 'type' => 'moderation', 'parent' => 'yonetim'],
        ['slug' => 'icerik', 'name' => 'İçerik', 'type' => 'content'],
        ['slug' => 'finans', 'name' => 'Finans', 'type' => 'economy', 'parent' => 'yonetim'],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->syncPermissions();
            $this->syncRoles();
            $this->seedBranches();
        });
    }

    private function syncPermissions(): void
    {
        /** @var array<string, array{group: string, description: string, sensitive: bool}> $catalog */
        $catalog = config('permissions');

        foreach ($catalog as $key => $meta) {
            Permission::query()->updateOrCreate(['key' => $key], [
                'group' => $meta['group'], 'description' => $meta['description'], 'is_sensitive' => $meta['sensitive'],
            ]);
        }

        Permission::query()->whereNotIn('key', array_keys($catalog))->delete();
    }

    private function syncRoles(): void
    {
        $permissionIds = Permission::query()->pluck('id', 'key');

        /** @var array<string, array{name: string, priority: int, color: string, description: string, permissions: list<string>, scoped: list<string>}> $roles */
        $roles = config('roles');

        foreach ($roles as $slug => $meta) {
            $role = Role::query()->updateOrCreate(['slug' => $slug], [
                'name' => $meta['name'], 'description' => $meta['description'], 'color' => $meta['color'],
                'priority' => $meta['priority'], 'is_staff' => true, 'is_system' => true,
            ]);

            $ids = $meta['permissions'] === ['*'] ? [] : array_map(fn (string $k): int => (int) $permissionIds[$k], $meta['permissions']);
            $role->permissions()->syncWithPivotValues($ids, ['created_at' => now()]);
        }
    }

    private function seedBranches(): void
    {
        foreach (self::BRANCHES as $b) {
            Branch::query()->firstOrCreate(['slug' => $b['slug']], [
                'name' => $b['name'], 'type' => $b['type'], 'description' => $b['description'] ?? null,
                'parent_id' => isset($b['parent']) ? Branch::query()->where('slug', $b['parent'])->value('id') : null,
            ]);
        }
    }
}
