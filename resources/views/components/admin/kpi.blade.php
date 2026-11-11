@props(['label', 'value', 'icon' => 'hash', 'note' => null])
{{-- KPI kutucuğu: büyük mono sayı + etiket + kaynak dipnotu (docs/DESIGN.md §7.3, FR-ADM-010). --}}
<div {{ $attributes->class(['rounded-lg border border-border bg-surface-1 p-4']) }}>
    <div class="flex items-center justify-between gap-2 text-text-muted">
        <span class="text-xs font-medium">{{ $label }}</span>
        <x-dynamic-component :component="'lucide-'.$icon" class="size-4 text-text-subtle" aria-hidden="true" />
    </div>
    <p class="mt-2 font-mono text-3xl font-semibold text-text tabular-nums">{{ number_format((int) $value, 0, ',', '.') }}</p>
    <p class="mt-1 text-[11px] text-text-subtle">{{ $note ?? __('admin.source_now') }}</p>
</div>
