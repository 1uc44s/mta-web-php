<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** docs/DATABASE.md §10.14 */
final class CommunityEvent extends Model
{
    use HasPublicUlid;
    use Publishable;

    protected $fillable = [
        'slug', 'title', 'summary', 'description', 'cover_media_id', 'starts_at', 'ends_at', 'location_label',
        'status', 'published_at', 'sort', 'seo_title', 'meta_description', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
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
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->published()
            ->where(fn (Builder $q) => $q->where('starts_at', '>=', now())->orWhere('ends_at', '>=', now()))
            ->orderBy('starts_at');
    }

    /** @return BelongsTo<Media, $this> */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }
}
