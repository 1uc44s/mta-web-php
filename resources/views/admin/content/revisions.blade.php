{{-- Revizyon listesi (sayfa/kural) — docs/ROUTES.md §6.8. Her revizyon, kaydetmeden önceki hâldir. --}}
<x-layouts.admin :title="__('content_admin.revisions').' · '.$title">
    <x-admin.header :title="$title" :description="__('content_admin.revisions')" :back="$back" :back-label="__('content_admin.edit', ['name' => ''])" />

    <ol class="space-y-4">
        @forelse ($revisions as $rev)
            <li>
                <x-admin.card>
                    <x-slot:title>#{{ $rev['id'] }} · {{ $rev['at']->format('d.m.Y H:i') }} · {{ $rev['author'] ?? '—' }}</x-slot:title>
                    @if ($rev['restore'])
                        <x-slot:actions>
                            @can('content.publish')
                                <form method="POST" action="{{ $rev['restore'] }}">@csrf<x-ui.button type="submit" size="sm" variant="secondary" icon="undo-2">Bu revizyonu geri yükle</x-ui.button></form>
                            @endcan
                        </x-slot:actions>
                    @endif
                    @if ($rev['note'])<p class="mb-3 text-text">{{ $rev['note'] }}</p>@endif
                    <details>
                        <summary class="cursor-pointer text-xs text-accent">Metni göster ({{ mb_strlen($rev['body']) }} karakter{{ $rev['body'] === $current ? ' · güncel ile aynı' : '' }})</summary>
                        <pre class="mt-2 max-h-96 overflow-auto rounded-md border border-border bg-bg p-3 font-mono text-xs whitespace-pre-wrap text-text">{{ $rev['body'] }}</pre>
                    </details>
                </x-admin.card>
            </li>
        @empty
            <li class="text-text-muted">Henüz revizyon yok. Kayıt her düzenlendiğinde önceki hâli burada saklanır.</li>
        @endforelse
    </ol>
</x-layouts.admin>
