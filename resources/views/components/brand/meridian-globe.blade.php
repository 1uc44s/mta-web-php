{{--
    Arka plan motifi (v2 "Gün Batımı Bulvarı", docs/DESIGN.md §1): ufkun üstünde yükselen çizgili güneş halkaları.
    Bileşen adı geriye uyumluluk için korunur (boş durumlar, hata sayfaları, kart yer tutucuları).
    Dekoratiftir; renk currentColor'dan gelir (ör. text-grid-line).
--}}
<svg {{ $attributes->merge(['viewBox' => '0 0 800 800', 'fill' => 'none', 'aria-hidden' => 'true', 'focusable' => 'false']) }}>
    <g stroke="currentColor" stroke-width="1" vector-effect="non-scaling-stroke">
        @foreach ([380, 320, 260, 200, 140] as $r)
            <circle cx="400" cy="400" r="{{ $r }}" />
        @endforeach
    </g>
    <g fill="currentColor">
        @foreach ([[430, 6], [470, 9], [510, 12], [550, 15], [590, 18], [630, 21]] as [$y, $h])
            @php($half = (int) round(sqrt(max(0, 380 ** 2 - ($y - 400) ** 2))))
            <rect x="{{ 400 - $half }}" y="{{ $y }}" width="{{ $half * 2 }}" height="{{ $h }}" />
        @endforeach
    </g>
</svg>
