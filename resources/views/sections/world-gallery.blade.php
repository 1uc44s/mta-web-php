{{-- CMS §4.2 world_gallery: yalnızca gerçek oyun içi medya ile render edilir (media_ids boşsa bölüm gizli). --}}
@if ($media->isNotEmpty())
    <section class="mx-auto max-w-[1240px] px-4 py-[clamp(4rem,3rem+4vw,7rem)] sm:px-8">
        <x-ui.section-header :number="$number" :eyebrow="$eyebrow ?? 'Dünya'" :title="$title ?? 'Şehri keşfet'" :intro="$intro ?? null" />
        <ul class="mt-10 grid grid-cols-2 gap-3 md:grid-cols-3">
            @foreach ($media as $item)
                <li class="overflow-hidden rounded-md border border-border {{ $loop->first ? 'col-span-2 row-span-2' : '' }}">
                    <img src="{{ $item->url(960) }}" srcset="{{ $item->srcset() }}" sizes="(min-width: 768px) 33vw, 50vw"
                         alt="{{ $item->altText() }}" width="{{ $item->width }}" height="{{ $item->height }}" loading="lazy" decoding="async"
                         class="h-full w-full object-cover">
                </li>
            @endforeach
        </ul>
    </section>
@endif
