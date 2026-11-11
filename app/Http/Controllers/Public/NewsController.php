<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Contracts\View\View;

/** docs/SRS.md FR-PUB-011 */
final class NewsController extends Controller
{
    public function index(?NewsCategory $category = null): View
    {
        $posts = NewsPost::query()->published()
            ->when($category, fn ($q) => $q->where('news_category_id', $category?->id))
            ->with(['category', 'cover'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('public.news.index', [
            'posts' => $posts,
            'categories' => NewsCategory::query()->whereHas('posts', fn ($q) => $q->published())->orderBy('sort')->get(),
            'activeCategory' => $category,
        ]);
    }

    public function show(MarkdownRenderer $markdown, string $post): View
    {
        $post = NewsPost::query()->published()->with(['category', 'cover', 'tags'])->where('slug', $post)->firstOrFail();

        return view('public.news.show', [
            'post' => $post,
            'html' => $markdown->render($post->body),
            'description' => $post->meta_description ?: ($post->excerpt ?: $markdown->excerpt($post->body)),
            'related' => NewsPost::query()->published()->whereKeyNot($post->id)
                ->where('news_category_id', $post->news_category_id)
                ->orderByDesc('published_at')->limit(3)->get(),
        ]);
    }
}
