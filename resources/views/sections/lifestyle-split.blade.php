{{-- CMS §4.2 lifestyle_split: iki yol (legal / illegal). columns: [title, text, link_label, link_route]. --}}
<section class="mx-auto max-w-[1240px] px-4 py-[clamp(4rem,3rem+4vw,7rem)] sm:px-8">
    <x-ui.section-header :number="$number" :eyebrow="$eyebrow ?? 'Yaşam'" :title="$title" :intro="$intro ?? null" />
    <div class="mt-12 grid gap-4 md:grid-cols-2">
        @foreach (($columns ?? []) as $column)
            @php($amber = $loop->index === 1)
            <article class="relative isolate overflow-hidden rounded-lg border border-border bg-surface-1 p-7 sm:p-9">
                <span aria-hidden="true" class="absolute inset-x-0 top-0 h-px {{ $amber ? 'bg-amber/60' : 'bg-accent/60' }}"></span>
                <x-brand.meridian-globe class="pointer-events-none absolute -right-40 -bottom-40 -z-10 size-80 text-grid-line" />
                <p class="eyebrow {{ $amber ? 'text-amber' : 'text-accent' }}">{{ $column['label'] ?? '' }}</p>
                <h3 class="mt-3 font-display text-h3 font-bold font-wide text-text">{{ $column['title'] }}</h3>
                <p class="mt-3 text-text-muted">{{ $column['text'] }}</p>
                @if (! empty($column['link_route']) && Route::has($column['link_route']))
                    <a href="{{ route($column['link_route'], $column['link_params'] ?? []) }}" class="mt-6 inline-flex items-center gap-2 text-sm font-medium {{ $amber ? 'text-amber' : 'text-accent' }} hover:underline">
                        {{ $column['link_label'] ?? __('public.details') }} <x-lucide-arrow-right class="size-4" aria-hidden="true" />
                    </a>
                @endif
            </article>
        @endforeach
    </div>
</section>
