{{-- Haber kartı. Kapak yoksa meridyen yer tutucu (sahte görsel yok — docs/DESIGN.md §5). --}}
<article class="group relative flex flex-col overflow-hidden glass rounded-[var(--radius-xl)] transition-colors hover:border-border-strong">
    <div class="relative aspect-[16/9] overflow-hidden border-b border-border bg-surface-2">
        @if ($post->cover)
            <img src="{{ $post->cover->url(960) }}" srcset="{{ $post->cover->srcset() }}" sizes="(min-width: 768px) 33vw, 100vw"
                 alt="{{ $post->cover->altText() }}" width="{{ $post->cover->width }}" height="{{ $post->cover->height }}" loading="lazy" decoding="async"
                 class="size-full object-cover transition-transform duration-[var(--dur-slow)] group-hover:scale-[1.02]">
        @else
            <x-brand.meridian-globe class="absolute top-1/2 left-1/2 size-[140%] -translate-x-1/2 -translate-y-1/2 text-grid-line" />
            <span class="absolute bottom-3 left-4 text-xs font-semibold text-text-subtle">{{ $post->category->name }}</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center gap-2 text-xs font-semibold text-text-subtle">
            <time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->published_at?->locale('tr')->translatedFormat('d M Y') }}</time>
            <span aria-hidden="true">·</span>
            <span>{{ $post->category->name }}</span>
            @if ($post->is_pinned)<x-ui.badge tone="amber" class="ml-auto">{{ __('public.pages.news.pinned') }}</x-ui.badge>@endif
        </div>
        <h3 class="mt-3 font-medium text-text"><a href="{{ route('public.news.show', $post->slug) }}" class="after:absolute after:inset-0">{{ $post->title }}</a></h3>
        @if ($post->excerpt)<p class="mt-2 line-clamp-3 text-sm text-text-muted">{{ $post->excerpt }}</p>@endif
    </div>
</article>
