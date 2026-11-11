<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** docs/DATABASE.md §10.11 */
final class Faction extends Model
{
    use Publishable;

    public const TYPES = ['legal', 'illegal', 'government', 'private'];

    protected $fillable = [
        'slug', 'name', 'type', 'logo_media_id', 'cover_media_id', 'summary', 'description', 'min_requirements',
        'application_type_id', 'applications_open', 'rule_category_id', 'accent_token',
        'status', 'published_at', 'sort', 'seo_title', 'meta_description', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['applications_open' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Media, $this> */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    /** @return BelongsTo<Media, $this> */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    /** @return BelongsTo<RuleCategory, $this> */
    public function ruleCategory(): BelongsTo
    {
        return $this->belongsTo(RuleCategory::class);
    }
}
