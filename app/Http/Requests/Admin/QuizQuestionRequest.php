<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** POST /admin/academy-quizzes/{quiz}/questions — soru + 2–6 seçenek, tam 1 doğru (DATABASE §10.17). */
final class QuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:500'],
            'options' => ['required', 'array', 'max:6'],
            'options.*' => ['nullable', 'string', 'max:255'],
            'correct' => ['required', 'integer', 'min:0', 'max:5'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $filled = array_filter((array) $this->input('options', []), fn ($o): bool => is_string($o) && trim($o) !== '');
            if (count($filled) < 2 || count($filled) > 6) {
                $validator->errors()->add('options', (string) __('academy.admin.errors.option_count'));
            }
            if (! array_key_exists((int) $this->input('correct'), $filled)) {
                $validator->errors()->add('correct', (string) __('academy.admin.errors.correct'));
            }
        }];
    }

    /** @return array{body: string, options: list<array{body: string, is_correct: bool}>} */
    public function question(): array
    {
        $data = $this->validated();
        $options = [];
        foreach ((array) $data['options'] as $index => $body) {
            if (is_string($body) && trim($body) !== '') {
                $options[] = ['body' => trim($body), 'is_correct' => (int) $index === (int) $data['correct']];
            }
        }

        return ['body' => trim((string) $data['body']), 'options' => $options];
    }
}
