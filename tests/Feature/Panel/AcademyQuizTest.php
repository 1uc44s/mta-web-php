<?php

declare(strict_types=1);

use App\Models\AcademyAttempt;
use App\Models\AcademyCourse;
use App\Models\AcademyQuestion;
use App\Models\AcademyQuiz;
use App\Models\AcademyTopic;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(SettingsRepository::class)->set('features.academy_quiz', true);
});

/** @return array{topic: AcademyTopic, quiz: AcademyQuiz, q1: AcademyQuestion, q2: AcademyQuestion, right: array<int, int>, wrong: array<int, int>} */
function quizFixture(int $maxAttempts = 3, int $cooldown = 60, bool $active = true): array
{
    $course = AcademyCourse::query()->create(['slug' => 'temel', 'title' => 'Temel', 'summary' => 'Özet', 'status' => 'published', 'published_at' => now()->subDay()]);
    $topic = AcademyTopic::query()->create(['academy_course_id' => $course->id, 'slug' => 'rp-nedir', 'title' => 'RP nedir', 'body' => 'İçerik', 'status' => 'published', 'published_at' => now()->subDay()]);
    $quiz = AcademyQuiz::query()->create(['academy_topic_id' => $topic->id, 'title' => 'RP sınavı', 'pass_percent' => 100, 'max_attempts' => $maxAttempts, 'cooldown_minutes' => $cooldown, 'is_active' => $active]);

    $make = function (string $body) use ($quiz): array {
        $q = AcademyQuestion::query()->create(['academy_quiz_id' => $quiz->id, 'body' => $body]);
        $right = $q->options()->create(['body' => 'Doğru şık', 'is_correct' => true, 'sort' => 0]);
        $wrong = $q->options()->create(['body' => 'Yanlış şık', 'is_correct' => false, 'sort' => 1]);

        return [$q, $right->id, $wrong->id];
    };
    [$q1, $r1, $w1] = $make('Soru bir?');
    [$q2, $r2, $w2] = $make('Soru iki?');

    return ['topic' => $topic, 'quiz' => $quiz, 'q1' => $q1, 'q2' => $q2, 'right' => [$q1->id => $r1, $q2->id => $r2], 'wrong' => [$q1->id => $w1, $q2->id => $r2]];
}

it('özellik kapalıyken veya sınav aktif değilken sınav sayfası 404', function (): void {
    $f = quizFixture(active: false);
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('panel.academy.quiz', $f['topic']->id))->assertNotFound();

    $f['quiz']->update(['is_active' => true]);
    app(SettingsRepository::class)->set('features.academy_quiz', false);
    $this->actingAs($user)->get(route('panel.academy.quiz', $f['topic']->id))->assertNotFound();
});

it('doğru seçenekler sınav geçilmeden istemciye gönderilmez', function (): void {
    $f = quizFixture();

    $this->actingAs(User::factory()->create())->get(route('panel.academy.quiz', $f['topic']->id))
        ->assertOk()
        ->assertSee('Soru bir?')
        ->assertDontSee(__('academy.quiz.correct'))
        ->assertDontSee('is_correct');
});

it('başarısız deneme kaydedilir, konu tamamlanmaz ve bekleme süresi uygulanır', function (): void {
    $f = quizFixture();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('panel.academy.quiz.submit', $f['topic']->id), ['answers' => $f['wrong']])->assertRedirect();

    $attempt = AcademyAttempt::query()->firstOrFail();
    expect($attempt->passed)->toBeFalse()->and($attempt->score_percent)->toBe(50)
        ->and(DB::table('academy_progress')->where('user_id', $user->id)->exists())->toBeFalse();

    $this->actingAs($user)->post(route('panel.academy.quiz.submit', $f['topic']->id), ['answers' => $f['right']])
        ->assertSessionHasErrors('answers');
    expect(AcademyAttempt::query()->count())->toBe(1);
});

it('geçen deneme konuyu tamamlar, doğru cevapları gösterir ve tekrar denemeyi engeller', function (): void {
    $f = quizFixture(cooldown: 0);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('panel.academy.quiz.submit', $f['topic']->id), ['answers' => $f['right']])
        ->assertRedirect(route('panel.academy.quiz', $f['topic']->id));

    expect(DB::table('academy_progress')->where(['user_id' => $user->id, 'academy_topic_id' => $f['topic']->id])->exists())->toBeTrue();

    $this->actingAs($user)->get(route('panel.academy.quiz', $f['topic']->id))->assertOk()->assertSee(__('academy.quiz.correct'));
    $this->actingAs($user)->post(route('panel.academy.quiz.submit', $f['topic']->id), ['answers' => $f['right']])
        ->assertSessionHasErrors('answers');
});

it('deneme sınırı dolunca yeni deneme kabul edilmez', function (): void {
    $f = quizFixture(maxAttempts: 2, cooldown: 0);
    $user = User::factory()->create();

    foreach ([1, 2] as $_) {
        $this->actingAs($user)->post(route('panel.academy.quiz.submit', $f['topic']->id), ['answers' => $f['wrong']])->assertSessionHasNoErrors();
    }
    $this->actingAs($user)->post(route('panel.academy.quiz.submit', $f['topic']->id), ['answers' => $f['right']])->assertSessionHasErrors('answers');

    expect(AcademyAttempt::query()->count())->toBe(2);
});

it('başka sorunun seçeneği veya eksik cevap reddedilir', function (): void {
    $f = quizFixture(cooldown: 0);
    $user = User::factory()->create();
    $foreign = [$f['q1']->id => $f['right'][$f['q2']->id], $f['q2']->id => $f['right'][$f['q2']->id]];

    $this->actingAs($user)->post(route('panel.academy.quiz.submit', $f['topic']->id), ['answers' => $foreign])
        ->assertSessionHasErrors('answers.'.$f['q1']->id);
    $this->actingAs($user)->post(route('panel.academy.quiz.submit', $f['topic']->id), ['answers' => [$f['q1']->id => $f['right'][$f['q1']->id]]])
        ->assertSessionHasErrors('answers.'.$f['q2']->id);

    expect(AcademyAttempt::query()->count())->toBe(0);
});

it('aktif sınavı olan konu "Okudum" ile tamamlanamaz', function (): void {
    $f = quizFixture();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('panel.academy.complete', $f['topic']->id))->assertSessionHasErrors('topic');
    expect(DB::table('academy_progress')->count())->toBe(0);
});

it('ACP: soru en az 2 seçenek ve dolu bir doğru seçenek ister; yalnızca academy.manage', function (): void {
    $f = quizFixture();
    $admin = staffUser('super_admin');

    $this->actingAs($admin)->post(route('admin.academy-quizzes.questions.store', $f['quiz']->id), ['body' => 'Yeni?', 'options' => ['Tek'], 'correct' => 0])
        ->assertSessionHasErrors('options');
    $this->actingAs($admin)->post(route('admin.academy-quizzes.questions.store', $f['quiz']->id), ['body' => 'Yeni?', 'options' => ['A', 'B', ''], 'correct' => 2])
        ->assertSessionHasErrors('correct');
    $this->actingAs($admin)->post(route('admin.academy-quizzes.questions.store', $f['quiz']->id), ['body' => 'Yeni?', 'options' => ['A', '', 'C'], 'correct' => 2])
        ->assertSessionHasNoErrors();

    $question = AcademyQuestion::query()->where('body', 'Yeni?')->firstOrFail();
    expect($question->options()->pluck('is_correct', 'body')->all())->toBe(['A' => false, 'C' => true]);

    $this->actingAs(staffUser('support_agent'))->get(route('admin.academy-quizzes.questions', $f['quiz']->id))->assertForbidden();
});
