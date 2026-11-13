<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Konu sınavı — docs/DATABASE.md §10.17, CMS §5.2. Konu başına 1 sınav.
 *
 * @property int $id
 * @property int $academy_topic_id
 * @property string $title
 * @property int $pass_percent
 * @property int $max_attempts
 * @property int $cooldown_minutes
 * @property bool $is_active
 */
final class AcademyQuiz extends Model
{
    protected $fillable = ['academy_topic_id', 'title', 'pass_percent', 'max_attempts', 'cooldown_minutes', 'is_active'];

    protected function casts(): array
    {
        return ['pass_percent' => 'integer', 'max_attempts' => 'integer', 'cooldown_minutes' => 'integer', 'is_active' => 'boolean'];
    }

    /** @return BelongsTo<AcademyTopic, $this> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(AcademyTopic::class, 'academy_topic_id');
    }

    /** @return HasMany<AcademyQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(AcademyQuestion::class)->orderBy('sort')->orderBy('id');
    }

    /** @return HasMany<AcademyAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(AcademyAttempt::class);
    }
}
