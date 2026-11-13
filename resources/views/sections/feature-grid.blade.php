{{-- CMS §4.2 feature_grid (v2): cam kartlar. items: [icon, title, text, system_slug?]. Yayında olmayan sisteme bağlı kart gösterilmez. --}}
<section class="relative isolate mx-auto max-w-[1240px] px-4 py-[clamp(5rem,3rem+5vw,9rem)] sm:px-8">
    <div class="flex flex-wrap items-end justify-between gap-6">
        <x-ui.section-header :eyebrow="$eyebrow ?? 'Oyun sistemleri'" :title="$title" :intro="$intro ?? null" />
        <x-ui.button variant="secondary" href="{{ route('public.systems.index') }}" icon-right="arrow-right">{{ __('public.home_sections.all_systems') }}</x-ui.button>
    </div>
    <ul @class([
        'mt-12 grid gap-4 sm:grid-cols-2',
        'lg:grid-cols-3' => ($variant ?? 'grid-3') !== 'grid-4',
        'lg:grid-cols-4' => ($variant ?? 'grid-3') === 'grid-4',
    ])>
        @foreach ($items as $item)
            <li class="glass group relative rounded-[var(--radius-xl)] p-7 transition-[transform,border-color] duration-[var(--dur-base)] ease-[var(--ease-out)] hover:-translate-y-1 hover:border-accent/50" data-reveal data-reveal-delay="{{ min(3, $loop->index % 4) }}">
                <span class="grid size-12 place-items-center rounded-2xl bg-accent-soft text-accent">
                    <x-dynamic-component :component="'lucide-'.($item['icon'] ?? 'sparkles')" class="size-6" aria-hidden="true" />
                </span>
                <h3 class="mt-6 font-display text-h3 font-black font-wide text-text">
                    @if (! empty($item['system_slug']))
                        <a href="{{ route('public.systems.show', $item['system_slug']) }}" class="after:absolute after:inset-0">{{ $item['title'] }}</a>
                    @else
                        {{ $item['title'] }}
                    @endif
                </h3>
                <p class="mt-2.5 text-text-muted">{{ $item['text'] }}</p>
                @if (! empty($item['system_slug']))
                    <x-lucide-arrow-up-right class="absolute top-7 right-7 size-5 text-text-subtle transition-colors group-hover:text-accent" aria-hidden="true" />
                @endif
            </li>
        @endforeach
    </ul>
</section>