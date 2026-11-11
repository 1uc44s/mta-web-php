<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GameJob;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** docs/SRS.md FR-PUB-007 */
final class GameJobController extends Controller
{
    public function index(Request $request): View
    {
        $class = in_array($request->query('class'), GameJob::CLASSES, true) ? (string) $request->query('class') : null;

        $jobs = GameJob::query()->published()
            ->when($class, fn ($q) => $q->where('class', $class))
            ->with('media')
            ->ordered()
            ->get();

        return view('public.jobs.index', ['jobs' => $jobs, 'activeClass' => $class]);
    }

    public function show(MarkdownRenderer $markdown, string $slug): View
    {
        $job = GameJob::query()->published()->with('media')->where('slug', $slug)->firstOrFail();

        return view('public.jobs.show', [
            'job' => $job,
            'description' => $markdown->render($job->description),
            'duties' => $markdown->render($job->duties),
            'progression' => $markdown->render($job->progression),
            'earnings' => $markdown->render($job->earnings_info),
        ]);
    }
}
