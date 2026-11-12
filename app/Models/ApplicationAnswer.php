<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Başvuru cevabı — docs/DATABASE.md §9.3. `question_label` gönderim anındaki soru metninin kopyasıdır.
 *
 * @property int $id
 * @property int $application_id
 * @property string $field_key
 * @property string $question_label
 * @property string|null $answer
 */
final class ApplicationAnswer extends Model
{
    protected $fillable = ['application_id', 'field_key', 'question_label', 'answer'];
}
