<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Sınav denemesi — docs/DATABASE.md §10.17.
 *
 * @property int $id
 * @property string $ulid
 * @property int $academy_quiz_id
 * @property int $user_id
 * @property int $score_percent
 * @property bool $passed
 * @property array<string, int> $answers
 * @property CarbonImmutable $created_at
 */
final class AcademyAttempt extends Model
{
    use HasPublicUlid;

    public const UPDATED_AT = null;

    protected $fillable = ['academy_quiz_id', 'user_id', 'score_percent', 'passed', 'answers'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'passed' => 'boolean', 'score_percent' => 'integer', 'created_at' => 'immutable_datetime'];
    }
}
