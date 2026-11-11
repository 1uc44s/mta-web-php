@props(['name' => config('app.name'), 'compact' => false])
{{-- İşaret: daire içinde sağa kaydırılmış tek boylam yayı (küre üzerindeki meridyen) — docs/DESIGN.md §2. --}}
<span {{ $attributes->class(['inline-flex items-center gap-2.5 text-text']) }}>
    <svg viewBox="0 0 32 32" fill="none" class="size-7 shrink-0" aria-hidden="true" focusable="false">
        <circle cx="16" cy="16" r="13" stroke="currentColor" stroke-width="2" />
        <path d="M19.5 3.6C12.4 8.3 12.4 23.7 19.5 28.4" class="stroke-accent" stroke-width="2.25" stroke-linecap="round" />
        <path d="M3.5 16h25" stroke="currentColor" stroke-width="1.25" stroke-opacity=".45" />
    </svg>
    @unless ($compact)
        <span class="font-display text-[1.05rem] leading-none font-extrabold tracking-[0.04em] uppercase font-expanded">
            {{ \Illuminate\Support\Str::of($name)->before(' ')->upper() }}
        </span>
    @endunless
</span>
