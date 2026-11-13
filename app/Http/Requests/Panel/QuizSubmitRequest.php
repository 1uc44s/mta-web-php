<?php

declare(strict_types=1);

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

/** POST /panel/academy/topics/{topic}/quiz — `answers[question_id]=option_id`. Eşleşme kontrolü AcademyQuizzes'ta. */
final class QuizSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['answers' => ['required', 'array', 'max:100'], 'answers.*' => ['required', 'integer', 'min:1']];
    }

    /** @return array<int|string, mixed> */
    public function answers(): array
    {
        return (array) $this->validated('answers', []);
    }
}
