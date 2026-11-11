{{-- Karakter detayı — docs/ROUTES.md §6.6. --}}
<x-layouts.admin :title="$character->name">
    <x-admin.header :title="$character->name" :description="$character->account?->server?->name.' · '.$character->mta_character_ref" :back="route('admin.characters.index')" :back-label="__('game.admin.characters')" />

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <x-admin.card>
            <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                <div><dt class="text-text-subtle">{{ __('game.character.account') }}</dt><dd><a class="text-text hover:text-accent" href="{{ route('admin.game-accounts.show', $character->account) }}">{{ $character->account?->account_name }}</a></dd></div>
                <div><dt class="text-text-subtle">{{ __('game.admin.user') }}</dt><dd class="text-text">{{ $character->account?->activeLink?->user?->username ?? '—' }}</dd></div>
                <div><dt class="text-text-subtle">{{ __('game.character.level') }}</dt><dd class="text-text">{{ $character->level ?? '—' }}</dd></div>
                <div><dt class="text-text-subtle">{{ __('game.character.playtime') }}</dt><dd class="text-text">{{ $character->playtime_minutes !== null ? __('game.character.hours', ['count' => intdiv($character->playtime_minutes, 60)]) : '—' }}</dd></div>
                <div><dt class="text-text-subtle">{{ __('game.character.faction') }}</dt><dd class="text-text">{{ $character->faction?->name ?? '—' }}</dd></div>
                <div><dt class="text-text-subtle">{{ __('game.character.job') }}</dt><dd class="text-text">{{ $character->job?->name ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-text-subtle">{{ __('game.character.profile') }}</dt>
                    <dd class="mt-1 space-y-1 text-text">
                        @forelse ((array) $character->public_profile as $key => $value)
                            <p><span class="text-text-subtle">{{ __('game.character.fields.'.$key) }}:</span> {{ $value }}</p>
                        @empty
                            —
                        @endforelse
                    </dd>
                </div>
            </dl>
        </x-admin.card>

        @can('characters.review')
            <x-admin.card :title="__('game.character.profile')">
                <form method="POST" action="{{ route('admin.characters.visibility', $character) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="hidden" value="{{ $character->is_profile_hidden ? 0 : 1 }}">
                    <x-ui.field name="reason" :label="__('admin.reason')" required><x-ui.input name="reason" multiline rows="2" required /></x-ui.field>
                    <x-ui.button type="submit" size="sm" variant="secondary" :icon="$character->is_profile_hidden ? 'eye' : 'eye-off'">
                        {{ $character->is_profile_hidden ? __('game.admin.show_profile') : __('game.admin.hide_profile') }}
                    </x-ui.button>
                </form>
            </x-admin.card>
        @endcan
    </div>
</x-layouts.admin>
