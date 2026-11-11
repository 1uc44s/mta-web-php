<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Contracts\View\View;

/** Hakkımızda, yasal sayfalar ve ek `standard` sayfalar (docs/SRS.md FR-PUB-014). */
final class PageController extends Controller
{
    public function __invoke(MarkdownRenderer $markdown, string $slug): View
    {
        $page = Page::query()->published()->where('slug', $slug)->where('slug', '!=', 'home')->firstOrFail();

        return view('public.page', [
            'page' => $page,
            'html' => $markdown->render($page->body),
            'description' => $page->meta_description ?: $markdown->excerpt($page->body),
        ]);
    }
}
