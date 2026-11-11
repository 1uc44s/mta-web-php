{{-- Karakter detayı — docs/SRS.md FR-ACC-009: yalnızca allowlist alanları. --}}
<x-layouts.panel :title="$character->name">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-ui.breadcrumb :items="[['label' => __('game.characters_title'), 'href' => route('panel.characters.index')], ['label' => $character->name]]" />
        <header class="flex flex-wrap items-center gap-3">
            <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ $character->name }}</h1>
            <x-ui.badge :tone="$character->status === 'active' ? 'accent' : 'neutral'">{{ __('game.character.status.'.$character->status) }}</x-ui.badge>
        </header>

        <dl class="grid gap-4 rounded-lg border border-border bg-surface-1 p-6 sm:grid-cols-2">
            <div><dt class="text-sm text-text-subtle">{{ __('game.character.level') }}</dt><dd class="text-text">{{ $character->level ?? '—' }}</dd></div>
            <div><dt class="text-sm text-text-subtle">{{ __('game.character.playtime') }}</dt><dd class="text-text">{{ $character->playtime_minutes !== null ? __('game.character.hours', ['count' => intdiv($character->playtime_minutes, 60)]) : '—' }}</dd></div>
            <div><dt class="text-sm text-text-subtle">{{ __('game.character.faction') }}</dt><dd class="text-text">{{ $character->faction?->name ?? '—' }}</dd></div>
            <div><dt class="text-sm text-text-subtle">{{ __('game.character.job') }}</dt><dd class="text-text">{{ $character->job?->name ?? '—' }}</dd></div>
            <div><dt class="text-sm text-text-subtle">{{ __('game.character.account') }}</dt><dd class="text-text">{{ $character->account?->account_name }}</dd></div>
            <div><dt class="text-sm text-text-subtle">{{ __('game.character.synced') }}</dt><dd class="text-text"><time datetime="{{ $character->synced_at->toIso8601String() }}">{{ $character->synced_at->diffForHumans() }}</time></dd></div>
        </dl>

        <section aria-labelledby="h-profile" class="rounded-lg border border-border bg-surface-1 p-6">
            <h2 id="h-profile" class="font-display text-h4 font-semibold text-text">{{ __('game.character.profile') }}</h2>
            @if ($character->is_profile_hidden)
                <p class="mt-2 text-sm text-text-muted">{{ __('game.character.hidden') }}</p>
            @else
                <dl class="mt-3 space-y-3">
                    @forelse ($character->publicFields() as $key => $value)
                        <div><dt class="text-sm text-text-subtle">{{ __('game.character.fields.'.$key) }}</dt><dd class="whitespace-pre-line text-text">{{ $value }}</dd></div>
                    @empty
                        <p class="text-sm text-text-muted">—</p>
                    @endforelse
                </dl>
            @endif
        </section>
    </div>
</x-layouts.panel>
