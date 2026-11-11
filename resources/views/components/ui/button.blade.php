@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconRight' => null,
])
{{-- docs/DESIGN.md §6.1 x-ui.button --}}
@php
    $classes = [
        'inline-flex items-center justify-center gap-2 rounded-md font-medium whitespace-nowrap select-none',
        'transition-[background-color,color,border-color] duration-[var(--dur-fast)] ease-[var(--ease-out)]',
        'disabled:pointer-events-none disabled:opacity-50 aria-busy:opacity-70',
        match ($size) {
            'sm' => 'min-h-9 px-3 text-sm',
            'lg' => 'min-h-12 px-6 text-base',
            default => 'min-h-11 px-4 text-[0.95rem]',
        },
        match ($variant) {
            'secondary' => 'border border-border-strong bg-surface-2 text-text hover:border-border-input hover:bg-surface-3',
            'ghost' => 'text-text-muted hover:bg-surface-2 hover:text-text',
            'danger' => 'bg-danger text-bg hover:opacity-90',
            'outline' => 'border border-border-strong text-text hover:border-accent hover:text-accent',
            default => 'bg-accent text-accent-fg hover:bg-accent-hover',
        },
    ];
    $iconClass = $size === 'sm' ? 'size-4' : 'size-[1.125rem]';
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-dynamic-component :component="'lucide-'.$icon" :class="$iconClass" aria-hidden="true" />@endif
        <span>{{ $slot }}</span>
        @if ($iconRight)<x-dynamic-component :component="'lucide-'.$iconRight" :class="$iconClass" aria-hidden="true" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)<x-dynamic-component :component="'lucide-'.$icon" :class="$iconClass" aria-hidden="true" />@endif
        <span>{{ $slot }}</span>
        @if ($iconRight)<x-dynamic-component :component="'lucide-'.$iconRight" :class="$iconClass" aria-hidden="true" />@endif
    </button>
@endif
