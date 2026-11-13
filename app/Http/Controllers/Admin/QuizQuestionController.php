<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Content\ManageQuizQuestions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuizQuestionRequest;
use App\Models\AcademyQuestion;
use App\Models\AcademyQuiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** ACP: sınav soruları — docs/ROUTES.md §6.8 (`academy.manage`). Doğru seçenek yalnızca personel ekranında görünür. */
final class QuizQuestionController extends Controller
{
    public function __construct(private readonly ManageQuizQuestions $questions) {}

    public function index(AcademyQuiz $quiz): View
    {
        $quiz->load(['topic:id,title', 'questions.options']);

        return view('admin.academy-quizzes.questions', ['quiz' => $quiz]);
    }

    public function store(QuizQuestionRequest $request, AcademyQuiz $quiz): RedirectResponse
    {
        $this->questions->add($quiz, $request->question());

        return redirect()->route('admin.academy-quizzes.questions', $quiz)->with('toast', __('academy.admin.saved'));
    }

    public function destroy(AcademyQuiz $quiz, AcademyQuestion $question): RedirectResponse
    {
        $this->questions->remove($quiz, $question);

        return redirect()->route('admin.academy-quizzes.questions', $quiz)->with('toast', __('academy.admin.deleted'));
    }
}
