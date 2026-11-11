<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** docs/DATABASE.md §10.9. Public'te yalnızca yayında + oyunda aktif olanlar görünür (docs/CMS.md §1). */
final class GameSystem extends Model
{
    use Publishable;

    protected $fillable = [
        'slug', 'game_system_category_id', 'name', 'summary', 'how_it_works', 'how_to_access', 'requirements',
        'media_id', 'video_url', 'is_live_in_game', 'last_reviewed_at',
        'status', 'published_at', 'sort', 'seo_title', 'meta_description', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_live_in_game' => 'boolean',
            'last_reviewed_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->published()->where('is_live_in_game', true);
    }

    /** @return BelongsTo<GameSystemCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(GameSystemCategory::class, 'game_system_category_id');
    }

    /** @return BelongsTo<Media, $this> */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
