<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Destek kategorisi — docs/DATABASE.md §8.1. Ticket'ın branşı kategoriden kopyalanır.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property int $branch_id
 * @property string $default_priority
 * @property bool $requires_linked_account
 * @property bool $is_active
 * @property int $sort
 */
final class SupportCategory extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'branch_id', 'default_priority', 'requires_linked_account', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['requires_linked_account' => 'boolean', 'is_active' => 'boolean', 'sort' => 'integer'];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort');
    }
}
