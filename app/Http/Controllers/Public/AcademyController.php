<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademyCourse;
use App\Models\AcademyTopic;
use App\Services\Bridge\ServerStatusResolver;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Contracts\View\View;

/** Akademi (müfredat) ve /start rehberi — docs/CMS.md §5, docs/SRS.md FR-PUB-009. */
final class AcademyController extends Controller
{
    public function index(): View
    {
        $courses = AcademyCourse::query()->published()
            ->withCount(['topics' => fn ($q) => $q->published()])
            ->ordered()
            ->get();

        return view('public.academy.index', ['courses' => $courses]);
    }

    public function course(string $course): View
    {
        $course = AcademyCourse::query()->published()->where('slug', $course)
            ->with(['topics' => fn ($q) => $q->published()])
            ->firstOrFail();

        return view('public.academy.course', ['course' => $course]);
    }

    public function topic(MarkdownRenderer $markdown, string $course, string $topic): View
    {
        $course = AcademyCourse::query()->published()->where('slug', $course)
            ->with(['topics' => fn ($q) => $q->published()])
            ->firstOrFail();

        /** @var AcademyTopic $current */
        $current = $course->topics->firstWhere('slug', $topic) ?? abort(404);
        $index = $course->topics->search(fn (AcademyTopic $t): bool => $t->is($current));
        $current->load(['rules' => fn ($q) => $q->published()]);

        return view('public.academy.topic', [
            'course' => $course,
            'topic' => $current,
            'html' => $markdown->render($current->body),
            'previous' => $index > 0 ? $course->topics[$index - 1] : null,
            'next' => $course->topics[$index + 1] ?? null,
            'position' => $index + 1,
        ]);
    }

    public function start(MarkdownRenderer $markdown, ServerStatusResolver $status): View
    {
        $course = AcademyCourse::query()->published()->where('is_onboarding', true)
            ->with(['topics' => fn ($q) => $q->published()])
            ->first();

        $steps = $course?->topics->map(fn (AcademyTopic $topic): array => [
            'topic' => $topic,
            'html' => $markdown->render($topic->body),
        ]) ?? collect();

        return view('public.start', ['course' => $course, 'steps' => $steps, 'status' => $status->primary()]);
    }
}
