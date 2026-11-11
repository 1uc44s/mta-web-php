@props(['tone' => 'neutral', 'icon' => null])
{{-- docs/DESIGN.md §6.1 x-ui.badge — durum rozetleri her zaman metin taşır (renk tek başına anlam taşımaz). --}}
<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 rounded-pill px-2.5 py-0.5 text-xs font-medium leading-5',
    match ($tone) {
        'accent' => 'bg-accent-soft text-accent',
        'amber', 'warning' => 'bg-amber-soft text-amber',
        'success' => 'bg-accent-soft text-success',
        'danger' => 'border border-danger/40 text-danger',
        'info' => 'border border-info/40 text-info',
        default => 'border border-border bg-surface-2 text-text-muted',
    },
]) }}>
    @if ($icon)<x-dynamic-component :component="'lucide-'.$icon" class="size-3.5" aria-hidden="true" />@endif
    {{ $slot }}
</span>
