<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;

/** docs/DATABASE.md §10.6 */
final class NavigationItem extends Model
{
    protected $fillable = [
        'location', 'parent_id', 'label', 'route_name', 'url', 'visibility', 'opens_new_tab', 'sort', 'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'opens_new_tab' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    /** @return HasMany<NavigationItem, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_visible', true)->orderBy('sort');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAt(Builder $query, string $location): Builder
    {
        return $query->where('location', $location)->whereNull('parent_id')->where('is_visible', true)->orderBy('sort');
    }

    public function href(): string
    {
        if ($this->route_name !== null && Route::has($this->route_name)) {
            return route($this->route_name);
        }

        return (string) $this->url;
    }

    public function isActive(): bool
    {
        return $this->route_name !== null && request()->routeIs($this->route_name, $this->route_name.'.*');
    }

    public function isVisibleTo(bool $authenticated): bool
    {
        return match ($this->visibility) {
            'guest' => ! $authenticated,
            'auth' => $authenticated,
            default => true,
        };
    }
}
