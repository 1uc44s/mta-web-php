<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * docs/DATABASE.md §3.3. Katalog kaynağı config/permissions.php (PermissionSeeder senkronlar).
 *
 * @property int $id
 * @property string $key
 * @property string $group
 * @property string $description
 * @property bool $is_sensitive
 */
final class Permission extends Model
{
    protected $fillable = ['key', 'group', 'description', 'is_sensitive'];

    protected function casts(): array
    {
        return ['is_sensitive' => 'boolean'];
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
