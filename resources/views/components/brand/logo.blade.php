@props(['compact' => false])
{{-- Marka logosu — docs/DESIGN.md §2 (v2). ACP → Marka'dan yüklenen logo varsa o; yoksa gün batımı işareti
     (çizgili güneş diski, ufuk çizgisi) + kısa ad. Ad ve görsel kodda sabit değildir. --}}
@php
    $logo = \App\Support\Brand::logoUrl();
    $short = \App\Support\Brand::shortName();
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-2.5 text-text']) }}>
    @if ($logo)
        <img src="{{ $logo }}" alt="{{ \App\Support\Brand::name() }}" class="h-8 w-auto max-w-[11rem] object-contain" height="32">
    @else
        <svg viewBox="0 0 32 32" fill="none" class="size-8 shrink-0" aria-hidden="true" focusable="false">
            <circle cx="16" cy="16" r="15" class="fill-accent-soft" />
            <path d="M6.5 19a9.5 9.5 0 0 1 19 0Z" class="fill-accent" />
            <path d="M5 21.5h22M8 24.5h16M11 27.5h10" class="stroke-accent" stroke-width="1.6" stroke-linecap="round" />
            <path d="M5 19h22" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        </svg>
        @unless ($compact)
            <span class="font-display text-[1.05rem] leading-none font-black tracking-[0.02em] uppercase font-expanded">{{ $short }}</span>
        @endunless
    @endif
</span>
