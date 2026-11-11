<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * docs/DATABASE.md §3.2, docs/ADMIN.md §2.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string|null $color
 * @property int $priority
 * @property bool $is_staff
 * @property bool $is_system
 */
final class Role extends Model
{
    public const SUPER_ADMIN = 'super_admin';

    protected $fillable = ['slug', 'name', 'description', 'color', 'priority', 'is_staff', 'is_system'];

    protected function casts(): array
    {
        return ['priority' => 'integer', 'is_staff' => 'boolean', 'is_system' => 'boolean'];
    }

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withPivot('created_at');
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')->withPivot(['assigned_by', 'expires_at', 'created_at']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === self::SUPER_ADMIN;
    }
}
