<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** docs/DATABASE.md §10.9 */
final class GameSystemCategory extends Model
{
    protected $fillable = ['slug', 'name', 'icon', 'sort'];

    /** @return HasMany<GameSystem, $this> */
    public function systems(): HasMany
    {
        return $this->hasMany(GameSystem::class);
    }
}
