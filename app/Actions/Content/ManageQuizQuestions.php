<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Models\AcademyQuestion;
use App\Models\AcademyQuiz;
use Illuminate\Support\Facades\DB;

/**
 * ACP sınav soruları — docs/ROUTES.md §6.8 (`academy.manage`), DATABASE §10.17. CMS içerik düzenlemeleriyle aynı
 * şekilde audit'e yazılmaz (yalnızca yayın geçişleri audit'lenir).
 */
final class ManageQuizQuestions
{
    /** @param  array{body: string, options: list<array{body: string, is_correct: bool}>}  $data */
    public function add(AcademyQuiz $quiz, array $data): AcademyQuestion
    {
        return DB::transaction(function () use ($quiz, $data): AcademyQuestion {
            /** @var AcademyQuestion $question */
            $question = $quiz->questions()->create([
                'body' => $data['body'],
                'sort' => (int) AcademyQuestion::query()->where('academy_quiz_id', $quiz->id)->max('sort') + 1,
            ]);
            foreach ($data['options'] as $i => $option) {
                $question->options()->create(['body' => $option['body'], 'is_correct' => $option['is_correct'], 'sort' => $i]);
            }

            return $question;
        });
    }

    public function remove(AcademyQuiz $quiz, AcademyQuestion $question): void
    {
        abort_unless($question->academy_quiz_id === $quiz->id, 404);
        $question->delete();
    }
}
