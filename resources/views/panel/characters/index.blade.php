{{-- Karakterlerim — docs/ROUTES.md §5.3, SRS FR-ACC-008. --}}
<x-layouts.panel :title="__('game.characters_title')">
    <div class="mx-auto max-w-4xl">
        <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('game.characters_title') }}</h1>

        <ul class="mt-6 grid gap-3 sm:grid-cols-2">
            @forelse ($characters as $c)
                <li>
                    <a href="{{ route('panel.characters.show', $c) }}" class="block rounded-lg border border-border bg-surface-1 p-5 hover:border-border-strong">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-display text-h4 font-semibold text-text">{{ $c->name }}</span>
                            <x-ui.badge :tone="$c->status === 'active' ? 'accent' : 'neutral'">{{ __('game.character.status.'.$c->status) }}</x-ui.badge>
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                            <div><dt class="text-text-subtle">{{ __('game.character.level') }}</dt><dd class="text-text">{{ $c->level ?? '—' }}</dd></div>
                            <div><dt class="text-text-subtle">{{ __('game.character.playtime') }}</dt><dd class="text-text">{{ $c->playtime_minutes !== null ? __('game.character.hours', ['count' => intdiv($c->playtime_minutes, 60)]) : '—' }}</dd></div>
                            <div><dt class="text-text-subtle">{{ __('game.character.faction') }}</dt><dd class="text-text">{{ $c->faction?->name ?? '—' }}</dd></div>
                            <div><dt class="text-text-subtle">{{ __('game.character.account') }}</dt><dd class="text-text">{{ $c->account?->account_name }}</dd></div>
                        </dl>
                    </a>
                </li>
            @empty
                <li class="sm:col-span-2"><x-ui.empty-state :title="__('game.characters_empty')" icon="users" /></li>
            @endforelse
        </ul>
    </div>
</x-layouts.panel>
