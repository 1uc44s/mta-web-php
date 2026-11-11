<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** docs/DATABASE.md §10.8 */
final class NewsCategory extends Model
{
    protected $fillable = ['slug', 'name', 'sort'];

    /** @return HasMany<NewsPost, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(NewsPost::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
