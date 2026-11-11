{{-- Bildirimler — docs/SRS.md FR-UCP-002: Laravel notifications + panel duyuruları. --}}
<x-layouts.panel title="Bildirimler">
    <div class="mx-auto max-w-3xl space-y-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-display text-h2 font-bold font-expanded text-text">Bildirimler</h1>
            <form method="POST" action="{{ route('panel.notifications.read-all') }}">@csrf<x-ui.button type="submit" size="sm" variant="secondary" icon="check-check">Tümünü okundu yap</x-ui.button></form>
        </div>

        <ul class="divide-y divide-border rounded-lg border border-border bg-surface-1">
            @forelse ($notifications as $n)
                <li @class(['flex flex-wrap items-center gap-3 px-5 py-4', 'opacity-70' => $n->read_at !== null])>
                    @if ($n->read_at === null)<span class="size-2 rounded-full bg-accent" aria-label="Okunmamış"></span>@endif
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-text">{{ $n->data['title'] ?? 'Bildirim' }}</p>
                        @if (! empty($n->data['subject']))<p class="truncate text-sm text-text-muted">{{ $n->data['subject'] }}</p>@endif
                    </div>
                    <time class="text-xs text-text-subtle" datetime="{{ $n->created_at?->toIso8601String() }}">{{ $n->created_at?->diffForHumans() }}</time>
                    <form method="POST" action="{{ route('panel.notifications.read', $n->id) }}">@csrf
                        <button type="submit" class="text-sm text-accent hover:underline">Aç</button>
                    </form>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-text-muted">Henüz bildirimin yok.</li>
            @endforelse
        </ul>
        <div>{{ $notifications->links() }}</div>

        @if ($announcements->isNotEmpty())
            <section aria-labelledby="h-ann">
                <h2 id="h-ann" class="font-display text-h4 font-semibold text-text">Duyurular</h2>
                <ul class="mt-3 space-y-3">
                    @foreach ($announcements as $a)
                        <li @class(['rounded-md border border-border px-5 py-4', 'opacity-70' => $a->is_read])>
                            <p class="font-medium text-text">{{ $a->title }}</p>
                            <x-ui.prose class="mt-1 text-sm" :html="$a->getAttribute('html')" />
                            @unless ($a->is_read)
                                <form method="POST" action="{{ route('panel.announcements.read', $a) }}" class="mt-2">@csrf<button type="submit" class="text-xs text-accent hover:underline">Okundu işaretle</button></form>
                            @endunless
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.panel>
