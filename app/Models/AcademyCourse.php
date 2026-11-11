<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Akademi "Ders" — docs/CMS.md §5, docs/DATABASE.md §10.17 */
final class AcademyCourse extends Model
{
    use Publishable;

    public const LEVELS = ['beginner', 'intermediate', 'advanced'];

    protected $fillable = [
        'slug', 'title', 'summary', 'level', 'cover_media_id', 'is_required', 'is_onboarding', 'estimated_minutes',
        'status', 'published_at', 'sort', 'seo_title', 'meta_description', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_onboarding' => 'boolean',
            'estimated_minutes' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<AcademyTopic, $this> */
    public function topics(): HasMany
    {
        return $this->hasMany(AcademyTopic::class)->orderBy('sort')->orderBy('id');
    }
}
