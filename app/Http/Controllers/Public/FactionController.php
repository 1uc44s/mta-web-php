<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faction;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** docs/SRS.md FR-PUB-008 */
final class FactionController extends Controller
{
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), Faction::TYPES, true) ? (string) $request->query('type') : null;

        $factions = Faction::query()->published()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->with('logo')
            ->ordered()
            ->get();

        return view('public.factions.index', ['factions' => $factions, 'activeType' => $type]);
    }

    public function show(MarkdownRenderer $markdown, string $slug): View
    {
        $faction = Faction::query()->published()->with(['logo', 'cover', 'ruleCategory'])->where('slug', $slug)->firstOrFail();

        return view('public.factions.show', [
            'faction' => $faction,
            'description' => $markdown->render($faction->description),
            'requirements' => $markdown->render($faction->min_requirements),
            'ruleCategoryPublished' => $faction->ruleCategory?->isPublished() ?? false,
        ]);
    }
}
