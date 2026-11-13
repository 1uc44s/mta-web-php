{{--
    Kodla çizilmiş San Andreas ufku (v2, docs/DESIGN.md §1 "Ufuk"): çizgili gün batımı diski, kuleler, palmiyeler.
    Sahte ekran görüntüsü veya stok karakter yerine. Dekoratif; renkler token'lardan.
--}}
<svg viewBox="0 0 600 520" fill="none" aria-hidden="true" focusable="false" {{ $attributes->class(['h-auto w-full']) }}>
    <g class="sun-drift">
        <circle cx="330" cy="250" r="150" class="fill-accent" />
        <circle cx="330" cy="250" r="150" class="fill-amber" opacity=".35" />
        @foreach ([258, 282, 304, 324, 342, 358, 372, 384] as $i => $y)
            <rect x="170" y="{{ $y }}" width="320" height="{{ 3 + $i }}" class="fill-bg" />
        @endforeach
    </g>
    {{-- Arka sıra kuleler --}}
    <path d="M40 520V330h46v-26h30v26h22v190Z" class="fill-surface-3" />
    <path d="M150 520V280l34-24 34 24v240Z" class="fill-surface-3" />
    <path d="M384 520V300h40v-40h20v40h36v220Z" class="fill-surface-3" />
    <path d="M492 520V346h68v174Z" class="fill-surface-3" />
    {{-- Ön sıra kuleler --}}
    <path d="M96 520V372h64v148Z" class="fill-border-strong" />
    <path d="M226 520V316h22v-34h8v34h22v204Z" class="fill-border-strong" />
    <path d="M300 520V354h56v166Z" class="fill-border-strong" />
    <path d="M440 520V386h70v134Z" class="fill-border-strong" />
    {{-- Pencereler (sodyum lamba) --}}
    @foreach ([[110, 392], [138, 392], [110, 420], [138, 448], [240, 340], [262, 340], [240, 380], [262, 420], [314, 376], [334, 404], [314, 432], [456, 404], [486, 404], [456, 432], [486, 460], [56, 360], [56, 400], [168, 310], [196, 340], [400, 330], [454, 330], [506, 370], [532, 400]] as [$x, $y])
        <rect x="{{ $x }}" y="{{ $y }}" width="8" height="10" rx="1" class="fill-amber" opacity=".7" />
    @endforeach
    {{-- Palmiyeler --}}
    <g class="fill-bg">
        <path d="M30 520c6-60 10-110 22-170l6 1c-10 60-14 110-18 169Z" />
        <path d="M55 352c-20-14-46-14-66-2 22-4 44 0 62 10ZM56 350c-6-22-26-38-50-40 20 8 36 22 44 42ZM58 350c10-20 32-30 56-28-22 4-40 14-50 32ZM58 352c20-6 42 2 56 18-18-10-38-14-54-10Z" />
        <path d="M566 520c-4-50-8-96-20-150l6-1c12 54 18 100 20 151Z" />
        <path d="M546 372c-18-12-42-12-60 0 20-4 40 0 56 8ZM548 370c-4-20-22-34-44-36 18 6 32 18 40 38ZM550 370c10-18 30-26 52-24-20 4-36 12-46 28ZM550 372c18-4 38 4 50 18-16-10-34-14-48-12Z" />
    </g>
    <rect x="0" y="508" width="600" height="12" class="fill-bg" />
</svg>