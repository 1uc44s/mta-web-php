@props(['status'])
{{-- Sunucu adresi plakası (docs/DESIGN.md §6.2, US-PUB-02). Kopyalama: resources/js/modules/copy.js --}}
<div {{ $attributes->class(['rounded-sm border border-border-input bg-surface-2']) }}>
    <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-2">
        <span class="eyebrow">{{ __('public.join.server_label') }}</span>
        <x-brand.status-pill :status="$status" compact class="border-0 bg-transparent px-0" />
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
        <code class="font-mono text-lg font-semibold tracking-tight break-all text-text sm:text-xl">{{ $status->address() }}</code>
        <button type="button" data-copy="{{ $status->address() }}" data-copied-label="{{ __('public.join.copied') }}"
                class="inline-flex min-h-10 items-center gap-2 rounded-md border border-border-strong px-3 text-sm font-medium text-text hover:border-accent hover:text-accent">
            <x-lucide-copy class="size-4" aria-hidden="true" />
            <span data-copy-label>{{ __('public.join.copy') }}</span>
        </button>
    </div>
</div>
