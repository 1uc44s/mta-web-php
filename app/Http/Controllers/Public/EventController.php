<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CommunityEvent;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Contracts\View\View;

/** docs/SRS.md FR-PUB-019 */
final class EventController extends Controller
{
    public function index(): View
    {
        return view('public.events.index', [
            'upcoming' => CommunityEvent::query()->upcoming()->with('cover')->get(),
            'past' => CommunityEvent::query()->published()
                ->where('starts_at', '<', now())
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '<', now()))
                ->orderByDesc('starts_at')->limit(6)->get(),
        ]);
    }

    public function show(MarkdownRenderer $markdown, string $event): View
    {
        $event = CommunityEvent::query()->published()->with('cover')->where('slug', $event)->firstOrFail();

        return view('public.events.show', ['event' => $event, 'html' => $markdown->render($event->description)]);
    }
}
