<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** docs/DATABASE.md §10.2 */
final class Page extends Model
{
    use Publishable;
    use SoftDeletes;

    protected $fillable = [
        'slug', 'title', 'template', 'body', 'version_label', 'effective_at', 'og_media_id', 'is_system',
        'status', 'published_at', 'sort', 'seo_title', 'meta_description', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_at' => 'immutable_datetime',
            'is_system' => 'boolean',
        ];
    }

    /** @return HasMany<PageSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort')->orderBy('id');
    }

    /** @return BelongsTo<Media, $this> */
    public function ogMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_media_id');
    }
}
