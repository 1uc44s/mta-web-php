<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademyCourse;
use App\Models\CommunityEvent;
use App\Models\Faction;
use App\Models\GameJob;
use App\Models\GameSystem;
use App\Models\NewsPost;
use App\Models\Page;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/** sitemap.xml, robots.txt, feed.xml (docs/SRS.md FR-PUB-015, NFR-SEO). */
final class SeoController extends Controller
{
    private const XML_PROLOG = '<?xml version="1.0" encoding="UTF-8"?>
';

    public function sitemap(): Response
    {
        $xml = Cache::remember('sitemap:v1', 3600, function (): string {
            $urls = collect([
                ['loc' => route('public.home'), 'lastmod' => null],
                ...collect(['public.systems.index', 'public.jobs.index', 'public.factions.index', 'public.start',
                    'public.rules', 'public.academy.index', 'public.news.index', 'public.events.index', 'public.faq',
                    'public.contact'])->map(fn (string $name): array => ['loc' => route($name), 'lastmod' => null]),
            ]);

            $add = function (iterable $models, callable $loc) use (&$urls): void {
                foreach ($models as $model) {
                    $urls->push(['loc' => $loc($model), 'lastmod' => $model->updated_at?->toAtomString()]);
                }
            };

            $add(Page::query()->published()->where('slug', '!=', 'home')->get(), fn (Page $p) => route('public.page', $p->slug));
            $add(GameSystem::query()->visible()->get(), fn (GameSystem $s) => route('public.systems.show', $s->slug));
            $add(GameJob::query()->published()->get(), fn (GameJob $j) => route('public.jobs.show', $j->slug));
            $add(Faction::query()->published()->get(), fn (Faction $f) => route('public.factions.show', $f->slug));
            $add(NewsPost::query()->published()->get(), fn (NewsPost $n) => route('public.news.show', $n->slug));
            $add(CommunityEvent::query()->published()->get(), fn (CommunityEvent $e) => route('public.events.show', $e->slug));
            $add(AcademyCourse::query()->published()->get(), fn (AcademyCourse $c) => route('public.academy.course', $c->slug));

            return self::XML_PROLOG.view('public.seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /panel/',
            'Disallow: /admin/',
            'Disallow: /api/',
            'Disallow: /webhooks/',
            '',
            'Sitemap: '.route('public.sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function feed(MarkdownRenderer $markdown): Response
    {
        $posts = NewsPost::query()->published()->orderByDesc('published_at')->limit(20)->get();

        return response(self::XML_PROLOG.view('public.seo.feed', [
            'posts' => $posts,
            'excerpts' => $posts->mapWithKeys(fn (NewsPost $p): array => [$p->id => $p->excerpt ?: $markdown->excerpt($p->body, 300)]),
        ])->render(), 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}
