@php
    $article = [
        '@type' => 'Article',
        'headline' => $post->title,
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'author' => ['@type' => 'Organization', 'name' => $post->author_label],
        'publisher' => ['@id' => url('/').'#org'],
        'mainEntityOfPage' => route('public.news.show', $post->slug),
    ];
@endphp
<x-layouts.public :title="$post->seo_title ?: $post->title" :description="$description" type="article"
                  :image="$post->cover?->url(1600)" :json-ld="[$article]">
    <x-ui.page-header :eyebrow="$post->category->name" :title="$post->title" :intro="$post->excerpt"
                      :crumbs="[['label' => __('public.pages.news.eyebrow'), 'href' => route('public.news.index')], ['label' => $post->title]]">
        <p class="mt-6 flex flex-wrap gap-x-4 font-mono text-xs text-text-subtle">
            <time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->published_at?->locale('tr')->translatedFormat('d F Y') }}</time>
            <span>{{ __('public.pages.news.by', ['author' => $post->author_label]) }}</span>
        </p>
    </x-ui.page-header>
    <div class="mx-auto max-w-[1240px] px-4 py-14 sm:px-8">
        @if ($post->cover)
            <img src="{{ $post->cover->url(1600) }}" srcset="{{ $post->cover->srcset() }}" sizes="(min-width: 1240px) 1176px, 100vw"
                 alt="{{ $post->cover->altText() }}" width="{{ $post->cover->width }}" height="{{ $post->cover->height }}" fetchpriority="high"
                 class="mb-12 w-full rounded-lg border border-border">
        @endif
        <x-ui.prose :html="$html" />
        @if ($post->tags->isNotEmpty())
            <ul class="mt-10 flex flex-wrap gap-2">
                @foreach ($post->tags as $tag)<li><x-ui.badge>#{{ $tag->name }}</x-ui.badge></li>@endforeach
            </ul>
        @endif
        @if ($related->isNotEmpty())
            <section class="mt-20 border-t border-border pt-10" aria-labelledby="related-title">
                <h2 id="related-title" class="font-display text-h3 font-bold font-wide text-text">{{ __('public.pages.news.related') }}</h2>
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach ($related->load(['category', 'cover']) as $item)
                        @include('public.news.partials.card', ['post' => $item])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.public>
