@props(['title', 'icon' => 'map-pin-off'])
{{-- docs/DESIGN.md §6.1 x-ui.empty-state --}}
<div {{ $attributes->class(['relative isolate overflow-hidden rounded-lg border border-dashed border-border-strong bg-surface-1 px-6 py-14 text-center']) }}>
    <x-brand.meridian-globe class="pointer-events-none absolute top-1/2 left-1/2 -z-10 size-96 -translate-x-1/2 -translate-y-1/2 text-grid-line" />
    <span class="mx-auto grid size-12 place-items-center rounded-md border border-border bg-surface-2 text-text-subtle">
        <x-dynamic-component :component="'lucide-'.$icon" class="size-5" aria-hidden="true" />
    </span>
    <p class="mt-4 font-medium text-text">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <div class="mx-auto mt-2 max-w-md text-sm text-text-muted">{{ $slot }}</div>
    @endif
</div>
