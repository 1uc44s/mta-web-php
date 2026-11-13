{{-- CMS §4.2 lifestyle_split (v2): iki yol — yasal (info mavisi) ve yeraltı (danger kırmızısı), ortada "ya da". columns: [label, title, text, link_label, link_route]. --}}
@php($columns = array_slice($columns ?? [], 0, 2))
<section class="relative isolate overflow-hidden py-[clamp(5rem,3rem+5vw,9rem)]">
    <x-ui.ghost-word class="top-10 left-1/2 -translate-x-1/2 text-[clamp(5rem,18vw,17rem)]">{{ __('public.home_sections.choose_ghost') }}</x-ui.ghost-word>
    <div class="mx-auto max-w-[1240px] px-4 sm:px-8">
        <x-ui.section-header :eyebrow="$eyebrow ?? 'Yaşam'" :title="$title" :intro="$intro ?? null" class="mx-auto text-center [&>p]:mx-auto" />
        <div class="relative mt-14 grid gap-4 md:grid-cols-2">
            @foreach ($columns as $column)
                @php($under = $loop->index === 1)
                <article @class([
                    'group relative isolate min-h-[22rem] overflow-hidden rounded-[var(--radius-xl)] border p-8 sm:p-10 transition-transform duration-[var(--dur-slow)] ease-[var(--ease-out)] hover:-translate-y-1',
                    'border-info/30 bg-info/10' => ! $under,
                    'border-danger/30 bg-danger/10' => $under,
                ]) data-reveal data-reveal-delay="{{ $loop->index + 1 }}">
                    <span aria-hidden="true" @class(['ghost-word absolute -right-6 -bottom-6 -z-10 text-[9rem] sm:text-[12rem]', 'text-info/10' => ! $under, 'text-danger/10' => $under])>{{ $column['label'] ?? '' }}</span>
                    <p @class(['eyebrow', 'text-info' => ! $under, 'text-danger' => $under])>{{ $column['label'] ?? '' }}</p>
                    <h3 class="mt-4 max-w-md font-display text-h3 font-black font-wide tracking-tight text-text lg:text-h2">{{ $column['title'] }}</h3>
                    <p class="mt-4 max-w-md text-lg text-text-muted">{{ $column['text'] }}</p>
                    @if (! empty($column['link_route']) && Route::has($column['link_route']))
                        <a href="{{ route($column['link_route'], $column['link_params'] ?? []) }}" @class([
                            'mt-8 inline-flex min-h-11 items-center gap-2 rounded-pill px-5 text-sm font-bold transition-colors',
                            'bg-info text-bg hover:opacity-90' => ! $under,
                            'bg-danger text-bg hover:opacity-90' => $under,
                        ])>
                            {{ $column['link_label'] ?? __('public.details') }} <x-lucide-arrow-right class="size-4" aria-hidden="true" />
                        </a>
                    @endif
                </article>
            @endforeach
            @if (count($columns) === 2)
                <span aria-hidden="true" class="glass absolute top-1/2 left-1/2 hidden size-16 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full font-display text-sm font-black text-text uppercase md:grid">{{ __('public.home_sections.or') }}</span>
            @endif
        </div>
    </div>
</section>