<x-layouts.public :title="$activeCategory?->name ?? __('public.pages.news.eyebrow')" :description="__('public.pages.news.intro')">
    <x-ui.page-header :eyebrow="__('public.pages.news.eyebrow')" :title="$activeCategory?->name ?? __('public.pages.news.title')" :intro="__('public.pages.news.intro')"
                      :crumbs="array_values(array_filter([['label' => __('public.pages.news.eyebrow'), 'href' => route('public.news.index')], $activeCategory ? ['label' => $activeCategory->name] : null]))">
        @if ($categories->isNotEmpty())
            <nav aria-label="Kategoriler" class="mt-8 flex flex-wrap gap-2">
                <a href="{{ route('public.news.index') }}" @if (! $activeCategory) aria-current="page" @endif class="inline-flex min-h-9 items-center rounded-pill border border-border px-4 text-sm text-text-muted hover:text-text aria-[current=page]:border-accent aria-[current=page]:bg-accent-soft aria-[current=page]:text-accent">{{ __('public.all') }}</a>
                @foreach ($categories as $category)
                    <a href="{{ route('public.news.category', $category) }}" @if ($activeCategory?->is($category)) aria-current="page" @endif class="inline-flex min-h-9 items-center rounded-pill border border-border px-4 text-sm text-text-muted hover:text-text aria-[current=page]:border-accent aria-[current=page]:bg-accent-soft aria-[current=page]:text-accent">{{ $category->name }}</a>
                @endforeach
            </nav>
        @endif
    </x-ui.page-header>
    <div class="mx-auto max-w-[1240px] px-4 py-14 sm:px-8">
        @if ($posts->isEmpty())
            <x-ui.empty-state :title="__('public.pages.news.empty')" icon="newspaper" />
        @else
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    @include('public.news.partials.card', ['post' => $post])
                @endforeach
            </div>
            {{ $posts->links() }}
        @endif
    </div>
</x-layouts.public>
