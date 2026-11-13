<?php

declare(strict_types=1);

namespace App\Actions\Panel;

use App\Models\AcademyAttempt;
use App\Models\AcademyCourse;
use App\Models\AcademyOption;
use App\Models\AcademyQuiz;
use App\Models\AcademyTopic;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Akademi konu sınavları — docs/DATABASE.md §10.17, CMS §5.2 (R2, Faz 10). Doğru seçenekler oyuncuya sınavı geçmeden
 * gönderilmez; geçen deneme konuyu `academy_progress` ile tamamlar. Deneme sınırı (0 = sınırsız) ve başarısız denemeden
 * sonraki bekleme süresi sunucuda uygulanır; eşzamanlı gönderim kullanıcı satırı kilidiyle sıralanır.
 */
final class AcademyQuizzes
{
    public function __construct(private readonly SettingsRepository $settings) {}

    /** Özellik açık, sınav aktif ve en az bir sorusu varsa sınav. */
    public function activeQuizFor(AcademyTopic $topic): ?AcademyQuiz
    {
        if (! $this->settings->bool('features.academy_quiz')) {
            return null;
        }

        return AcademyQuiz::query()
            ->where('academy_topic_id', $topic->id)
            ->where('is_active', true)
            ->whereHas('questions')
            ->first();
    }

    /** Konu + ders yayında ve sınav aktif değilse 404. */
    public function quizForTopicOrFail(AcademyTopic $topic): AcademyQuiz
    {
        $coursePublished = AcademyCourse::query()->published()->whereKey($topic->academy_course_id)->exists();
        abort_unless($topic->isPublished() && $coursePublished, 404);

        return $this->activeQuizFor($topic) ?? abort(404);
    }

    /**
     * @return array{attempts: int, remaining: int|null, locked_until: CarbonImmutable|null, passed: AcademyAttempt|null, last: AcademyAttempt|null}
     */
    public function state(User $user, AcademyQuiz $quiz): array
    {
        $attempts = AcademyAttempt::query()->where(['academy_quiz_id' => $quiz->id, 'user_id' => $user->id])->orderByDesc('id')->get();
        $last = $attempts->first();
        $passed = $attempts->firstWhere('passed', true);

        $lockedUntil = null;
        if ($passed === null && $last !== null && ! $last->passed && $quiz->cooldown_minutes > 0) {
            $until = $last->created_at->addMinutes($quiz->cooldown_minutes);
            $lockedUntil = $until->isFuture() ? $until : null;
        }

        return [
            'attempts' => $attempts->count(),
            'remaining' => $quiz->max_attempts === 0 ? null : max(0, $quiz->max_attempts - $attempts->count()),
            'locked_until' => $lockedUntil,
            'passed' => $passed,
            'last' => $last,
        ];
    }

    /**
     * Soru + seçenekler. Doğru bilgisi yalnızca `$reveal` (sınavı geçmiş oyuncu) için doldurulur.
     *
     * @return list<array{id: int, body: string, options: list<array{id: int, body: string, correct: bool|null}>}>
     */
    public function questions(AcademyQuiz $quiz, bool $reveal): array
    {
        return $quiz->questions()->with('options')->get()->map(fn ($q): array => [
            'id' => $q->id,
            'body' => $q->body,
            'options' => $q->options->map(fn (AcademyOption $o): array => [
                'id' => $o->id, 'body' => $o->body, 'correct' => $reveal ? $o->is_correct : null,
            ])->values()->all(),
        ])->values()->all();
    }

    /** @param  array<int|string, mixed>  $answers  question_id => option_id */
    public function submit(User $user, AcademyQuiz $quiz, array $answers): AcademyAttempt
    {
        return DB::transaction(function () use ($user, $quiz, $answers): AcademyAttempt {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $state = $this->state($user, $quiz);
            if ($state['passed'] !== null) {
                throw ValidationException::withMessages(['answers' => __('academy.quiz.errors.already_passed')]);
            }
            if ($state['remaining'] === 0) {
                throw ValidationException::withMessages(['answers' => __('academy.quiz.errors.no_attempts')]);
            }
            if ($state['locked_until'] !== null) {
                throw ValidationException::withMessages(['answers' => __('academy.quiz.errors.cooldown', ['time' => $state['locked_until']->format('H:i')])]);
            }

            $questions = $quiz->questions()->with('options')->get();
            $correct = 0;
            $normalized = [];

            foreach ($questions as $question) {
                $chosen = (int) ($answers[$question->id] ?? 0);
                $option = $question->options->firstWhere('id', $chosen);
                if ($option === null) {
                    throw ValidationException::withMessages(["answers.{$question->id}" => __('academy.quiz.errors.unanswered')]);
                }
                $normalized[(string) $question->id] = $option->id;
                if ($option->is_correct) {
                    $correct++;
                }
            }

            $score = $questions->isEmpty() ? 0 : intdiv($correct * 100, $questions->count());
            $passed = $score >= $quiz->pass_percent;

            $attempt = AcademyAttempt::query()->create([
                'academy_quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'score_percent' => $score,
                'passed' => $passed,
                'answers' => $normalized,
            ]);

            if ($passed) {
                DB::table('academy_progress')->insertOrIgnore([
                    'user_id' => $user->id,
                    'academy_topic_id' => $quiz->academy_topic_id,
                    'completed_at' => now(),
                ]);
            }

            return $attempt;
        });
    }
}
