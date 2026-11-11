{{-- CMS §4.2 feature_grid. items: [icon, title, text, system_slug?]. Yayında olmayan sisteme bağlı kart gösterilmez. --}}
<section class="mx-auto max-w-[1240px] px-4 py-[clamp(4rem,3rem+4vw,7rem)] sm:px-8">
    <x-ui.section-header :number="$number" :eyebrow="$eyebrow ?? 'Oyun sistemleri'" :title="$title" :intro="$intro ?? null" />
    <ul @class([
        'mt-12 grid gap-px overflow-hidden rounded-lg border border-border bg-border sm:grid-cols-2',
        'lg:grid-cols-3' => ($variant ?? 'grid-3') !== 'grid-4',
        'lg:grid-cols-4' => ($variant ?? 'grid-3') === 'grid-4',
    ])>
        @foreach ($items as $item)
            <li class="group relative bg-surface-1 p-6 transition-colors hover:bg-surface-2 sm:p-7">
                <span class="grid size-11 place-items-center rounded-md border border-accent/25 bg-accent-soft text-accent">
                    <x-dynamic-component :component="'lucide-'.($item['icon'] ?? 'sparkles')" class="size-5" aria-hidden="true" />
                </span>
                <h3 class="mt-5 font-medium text-text">
                    @if (! empty($item['system_slug']))
                        <a href="{{ route('public.systems.show', $item['system_slug']) }}" class="after:absolute after:inset-0">{{ $item['title'] }}</a>
                    @else
                        {{ $item['title'] }}
                    @endif
                </h3>
                <p class="mt-2 text-sm text-text-muted">{{ $item['text'] }}</p>
                @if (! empty($item['system_slug']))
                    <x-lucide-arrow-up-right class="absolute top-6 right-6 size-4 text-text-subtle transition-colors group-hover:text-accent" aria-hidden="true" />
                @endif
            </li>
        @endforeach
    </ul>
</section>
