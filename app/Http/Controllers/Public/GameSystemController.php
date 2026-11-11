<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GameSystem;
use App\Models\GameSystemCategory;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Contracts\View\View;

/** docs/SRS.md FR-PUB-006: yalnızca yayında + oyunda aktif sistemler. */
final class GameSystemController extends Controller
{
    public function index(): View
    {
        $categories = GameSystemCategory::query()
            ->with(['systems' => fn ($q) => $q->visible()->ordered()])
            ->orderBy('sort')
            ->get()
            ->filter(fn (GameSystemCategory $category): bool => $category->systems->isNotEmpty())
            ->values();

        return view('public.systems.index', ['categories' => $categories]);
    }

    public function show(MarkdownRenderer $markdown, string $slug): View
    {
        $system = GameSystem::query()->visible()->with(['category', 'media'])->where('slug', $slug)->firstOrFail();

        return view('public.systems.show', [
            'system' => $system,
            'howItWorks' => $markdown->render($system->how_it_works),
            'howToAccess' => $markdown->render($system->how_to_access),
            'requirements' => $markdown->render($system->requirements),
        ]);
    }
}
