@props(['number' => null])
{{-- Koordinat etiketi: "§02 · OYUN SİSTEMLERİ" (docs/DESIGN.md §6.2). Numarasız kullanımda "§ OYUN SİSTEMLERİ". --}}
<p {{ $attributes->class(['eyebrow flex items-center gap-2']) }}>
    @if ($number !== null)
        <span class="text-accent">§{{ str_pad((string) $number, 2, '0', STR_PAD_LEFT) }}</span>
        <span aria-hidden="true">·</span>
    @else
        <span class="text-accent" aria-hidden="true">§</span>
    @endif
    <span>{{ $slot }}</span>
</p>
