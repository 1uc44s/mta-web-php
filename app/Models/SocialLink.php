<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** docs/DATABASE.md §10.7 */
final class SocialLink extends Model
{
    protected $fillable = ['platform', 'label', 'url', 'sort', 'is_visible'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->orderBy('sort');
    }
}
