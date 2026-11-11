<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSection;
use App\Services\Content\SectionRegistry;
use Illuminate\Contracts\View\View;

/** docs/SRS.md FR-PUB-003: ana sayfa, `pages(slug=home)` bölümlerinden render edilir. */
final class HomeController extends Controller
{
    public function __invoke(SectionRegistry $registry): View
    {
        $page = Page::query()->published()->where('slug', 'home')->first();

        $sections = $page === null ? collect() : $page->sections()->where('is_visible', true)->get()
            ->filter(fn (PageSection $section): bool => $registry->supports($section->type))
            ->map(fn (PageSection $section): ?array => ($data = $registry->viewData($section)) === null
                ? null
                : ['view' => $registry->view($section->type), 'data' => $data, 'type' => $section->type])
            ->filter()
            ->values();

        return view('public.home', ['page' => $page, 'sections' => $sections]);
    }
}
