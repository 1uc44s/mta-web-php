<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Akademi "Konu" — docs/CMS.md §5 */
final class AcademyTopic extends Model
{
    use Publishable;

    protected $fillable = [
        'academy_course_id', 'slug', 'title', 'body', 'step_label', 'estimated_minutes',
        'status', 'published_at', 'sort', 'seo_title', 'meta_description', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['estimated_minutes' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<AcademyCourse, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(AcademyCourse::class, 'academy_course_id');
    }

    /** @return BelongsToMany<Rule, $this> */
    public function rules(): BelongsToMany
    {
        return $this->belongsToMany(Rule::class, 'academy_topic_rule');
    }
}
