@props(['number' => null])
{{-- Bölüm çipi (v2, docs/DESIGN.md §1): cam hap + gün batımı noktası + kısa etiket. `number` geriye uyumluluk için kabul edilir, gösterilmez. --}}
<p {{ $attributes->class(['glass eyebrow inline-flex w-fit items-center gap-2 rounded-pill px-3.5 py-1.5 text-text-muted']) }}>
    <span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>
    <span>{{ $slot }}</span>
</p>