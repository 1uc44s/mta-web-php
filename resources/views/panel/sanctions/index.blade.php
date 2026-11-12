{{-- Cezalarım — docs/SRS.md FR-MOD-004 (veren "Yönetim Ekibi"; iç not yok). --}}
<x-layouts.panel :title="__('moderation.title')">
    <div class="mx-auto max-w-4xl">
        <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('moderation.title') }}</h1>
        <ul class="mt-6 space-y-3">
            @forelse ($sanctions as $s)
                <li>
                    <a href="{{ route('panel.sanctions.show', $s) }}" class="flex flex-wrap items-center gap-3 rounded-lg border border-border bg-surface-1 px-5 py-4 hover:border-border-strong">
                        <x-ui.badge :tone="$s->status === 'active' ? 'danger' : 'neutral'">{{ __('moderation.types.'.$s->type) }}</x-ui.badge>
                        <span class="min-w-0 flex-1 truncate text-text">{{ $s->public_reason }}</span>
                        <span class="text-xs text-text-subtle">{{ __('moderation.status.'.$s->status) }} · {{ $s->starts_at->format('d.m.Y') }}</span>
                    </a>
                </li>
            @empty
                <li><x-ui.empty-state :title="__('moderation.empty')" icon="shield-check" /></li>
            @endforelse
        </ul>
    </div>
</x-layouts.panel>
