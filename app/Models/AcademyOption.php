<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Soru seçeneği — docs/DATABASE.md §10.17. `is_correct` oyuncuya sınavı geçmeden gönderilmez.
 *
 * @property int $id
 * @property int $academy_question_id
 * @property string $body
 * @property bool $is_correct
 * @property int $sort
 */
final class AcademyOption extends Model
{
    public $timestamps = false;

    protected $fillable = ['academy_question_id', 'body', 'is_correct', 'sort'];

    protected $hidden = ['is_correct'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}
