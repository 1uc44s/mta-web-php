{{--
    Meridyen küre — "Gece Haritası" imza öğesi (docs/DESIGN.md §1, §6.2).
    Boylamlar: aynı yükseklikte, genişliği daralan elipsler. Enlemler: yatay çizgiler.
    Dekoratiftir; renk currentColor'dan gelir (ör. text-grid-line).
--}}
<svg {{ $attributes->merge(['viewBox' => '0 0 800 800', 'fill' => 'none', 'aria-hidden' => 'true', 'focusable' => 'false']) }}>
    <g stroke="currentColor" stroke-width="1" vector-effect="non-scaling-stroke">
        <circle cx="400" cy="400" r="380" />
        @foreach ([380, 330, 268, 194, 112, 34] as $rx)
            <ellipse cx="400" cy="400" rx="{{ $rx }}" ry="380" />
        @endforeach
        @foreach ([-300, -210, -110, 0, 110, 210, 300] as $offset)
            @php($half = (int) round(sqrt(380 ** 2 - $offset ** 2)))
            <line x1="{{ 400 - $half }}" y1="{{ 400 + $offset }}" x2="{{ 400 + $half }}" y2="{{ 400 + $offset }}" />
        @endforeach
    </g>
</svg>
