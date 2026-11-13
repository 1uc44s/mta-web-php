<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel;

use App\Actions\Panel\AcademyQuizzes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\QuizSubmitRequest;
use App\Models\AcademyTopic;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Panel: konu sınavı — docs/ROUTES.md §5.3 (F:academy_quiz), DATABASE §10.17. */
final class AcademyQuizController extends Controller
{
    public function __construct(private readonly AcademyQuizzes $quizzes) {}

    public function show(Request $request, AcademyTopic $topic): View
    {
        /** @var User $user */
        $user = $request->user();
        $quiz = $this->quizzes->quizForTopicOrFail($topic);
        $state = $this->quizzes->state($user, $quiz);

        return view('panel.academy-quiz', [
            'topic' => $topic,
            'quiz' => $quiz,
            'state' => $state,
            'questions' => $this->quizzes->questions($quiz, $state['passed'] !== null),
            'chosen' => $state['passed']->answers ?? [],
        ]);
    }

    public function submit(QuizSubmitRequest $request, AcademyTopic $topic): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $quiz = $this->quizzes->quizForTopicOrFail($topic);
        $attempt = $this->quizzes->submit($user, $quiz, $request->answers());

        return redirect()->route('panel.academy.quiz', $topic->id)->with('toast', $attempt->passed
            ? __('academy.quiz.passed', ['score' => $attempt->score_percent])
            : __('academy.quiz.failed', ['score' => $attempt->score_percent, 'pass' => $quiz->pass_percent]));
    }
}
