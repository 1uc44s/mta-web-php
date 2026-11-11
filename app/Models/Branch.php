<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Branş (birim) — docs/DATABASE.md §3.6, docs/ADMIN.md §5.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $type
 * @property int|null $faction_id
 * @property int|null $parent_id
 * @property string|null $description
 * @property bool $is_active
 */
final class Branch extends Model
{
    public const TYPES = ['support', 'moderation', 'faction', 'content', 'economy', 'game', 'general'];

    protected $fillable = ['slug', 'name', 'type', 'faction_id', 'parent_id', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_members')->withPivot(['is_lead', 'assigned_by', 'expires_at', 'created_at']);
    }

    /** @return BelongsTo<Faction, $this> */
    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
