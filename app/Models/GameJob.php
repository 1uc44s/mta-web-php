<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** docs/DATABASE.md §10.10 (meslekler — Laravel `jobs` tablosuyla çakışmamak için game_jobs, D-10). */
final class GameJob extends Model
{
    use Publishable;

    public const CLASSES = ['legal', 'special', 'event'];

    protected $fillable = [
        'slug', 'name', 'summary', 'class', 'description', 'duties', 'progression', 'earnings_info',
        'access_requirement', 'media_id', 'application_type_id', 'is_active',
        'status', 'published_at', 'sort', 'seo_title', 'meta_description', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Media, $this> */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
