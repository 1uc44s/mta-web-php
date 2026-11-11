{{-- CMS §4.2 news_latest (otomatik). --}}
<section class="mx-auto max-w-[1240px] px-4 py-[clamp(4rem,3rem+4vw,7rem)] sm:px-8">
    <div class="flex flex-wrap items-end justify-between gap-6">
        <x-ui.section-header :number="$number" :eyebrow="$eyebrow ?? 'Haberler'" :title="$title ?? 'Şehirden son gelişmeler'" />
        <x-ui.button variant="ghost" href="{{ route('public.news.index') }}" icon-right="arrow-right">{{ __('public.home_sections.all_news') }}</x-ui.button>
    </div>
    <div class="mt-10 grid gap-4 md:grid-cols-3">
        @foreach ($posts as $post)
            @include('public.news.partials.card', ['post' => $post])
        @endforeach
    </div>
</section>
