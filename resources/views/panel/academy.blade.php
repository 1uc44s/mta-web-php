{{-- Panel: Akademi ilerlemesi — docs/CMS.md §5.2. İlerleme = tamamlanan / yayındaki konu. --}}
<x-layouts.public :title="'Akademi ilerlemem'">
    <section class="mx-auto max-w-[1240px] px-4 py-14 sm:px-8">
        <x-brand.eyebrow>Oyuncu paneli</x-brand.eyebrow>
        <h1 class="mt-4 font-display text-h1 font-bold font-expanded text-text">Akademi ilerlemem</h1>
        <p class="mt-3 max-w-2xl text-text-muted">Konuları okuyup "Okudum" dediğinde ilerlemen burada görünür. Zorunlu dersler rozetle işaretlidir.</p>

        <ul class="mt-10 grid gap-4 md:grid-cols-2">
            @forelse ($progress as $row)
                <li class="rounded-lg border border-border bg-surface-1 p-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('public.academy.course', $row['course']) }}" class="font-display text-h4 font-semibold text-text hover:text-accent">{{ $row['course']->title }}</a>
                        @if ($row['course']->is_required)<x-ui.badge tone="amber">Zorunlu</x-ui.badge>@endif
                        @if ($row['total'] > 0 && $row['done'] === $row['total'])<x-ui.badge tone="success" icon="check">Tamamlandı</x-ui.badge>@endif
                    </div>
                    <p class="mt-2 text-sm text-text-muted">{{ $row['course']->summary }}</p>
                    <label class="mt-4 block text-xs text-text-subtle" for="p-{{ $row['course']->id }}">{{ $row['done'] }} / {{ $row['total'] }} konu</label>
                    <progress id="p-{{ $row['course']->id }}" max="{{ max(1, $row['total']) }}" value="{{ $row['done'] }}" class="mt-1 h-2 w-full overflow-hidden rounded-pill accent-[var(--color-accent)]"></progress>
                </li>
            @empty
                <li class="text-text-muted">Henüz yayında ders yok.</li>
            @endforelse
        </ul>
    </section>
</x-layouts.public>
