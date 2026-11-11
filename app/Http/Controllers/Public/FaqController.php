<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Contracts\View\View;

/** docs/SRS.md FR-PUB-012 */
final class FaqController extends Controller
{
    public function __invoke(MarkdownRenderer $markdown): View
    {
        $faqs = Faq::query()->published()->ordered()->get();

        return view('public.faq', [
            'groups' => $faqs->groupBy('group'),
            'answers' => $faqs->mapWithKeys(fn (Faq $faq): array => [$faq->id => $markdown->render($faq->answer)]),
        ]);
    }
}
