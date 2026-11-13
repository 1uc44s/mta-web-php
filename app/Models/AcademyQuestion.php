<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sınav sorusu — docs/DATABASE.md §10.17.
 *
 * @property int $id
 * @property int $academy_quiz_id
 * @property string $body
 * @property int $sort
 */
final class AcademyQuestion extends Model
{
    protected $fillable = ['academy_quiz_id', 'body', 'sort'];

    /** @return HasMany<AcademyOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(AcademyOption::class)->orderBy('sort')->orderBy('id');
    }
}
