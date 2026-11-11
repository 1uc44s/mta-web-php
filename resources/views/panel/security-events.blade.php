{{-- Güvenlik olaylarım (son 90 gün) — docs/ROUTES.md §5.8. --}}
<x-layouts.panel title="Güvenlik olayları">
    <div class="mx-auto max-w-4xl">
        <h1 class="font-display text-h2 font-bold font-expanded text-text">Güvenlik olayları</h1>
        <p class="mt-2 text-text-muted">Son 90 gün. IP adresleri maskeli gösterilir.</p>
        <ul class="mt-6 divide-y divide-border rounded-lg border border-border bg-surface-1">
            @forelse ($events as $e)
                <li class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm">
                    <span class="font-mono text-xs text-text">{{ $e->event }}</span>
                    <span class="text-text-muted">{{ $e->user_agent_summary ?? '—' }}</span>
                    <span class="font-mono text-xs text-text-subtle">{{ $e->ip_masked ?? '' }}</span>
                    <time class="ml-auto text-xs text-text-subtle" datetime="{{ $e->created_at->toIso8601String() }}">{{ $e->created_at->format('d.m.Y H:i') }}</time>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-text-muted">Kayıtlı olay yok.</li>
            @endforelse
        </ul>
        <div class="mt-4">{{ $events->links() }}</div>
    </div>
</x-layouts.panel>
