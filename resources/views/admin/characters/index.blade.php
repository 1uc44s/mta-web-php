{{-- Karakterler — docs/ROUTES.md §6.6. --}}
<x-layouts.admin :title="__('game.admin.characters')">
    <x-admin.header :title="__('game.admin.characters')" />

    <form method="GET" action="{{ route('admin.characters.index') }}" role="search" class="mb-4 flex flex-wrap items-end gap-2">
        <div class="min-w-64 flex-1">
            <label for="q" class="sr-only">{{ __('admin.search') }}</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="{{ __('admin.search') }}"
                   class="h-10 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text placeholder:text-text-subtle focus:border-accent focus:outline-none">
        </div>
        <x-ui.button type="submit" size="sm" variant="secondary" icon="search">{{ __('admin.filter') }}</x-ui.button>
    </form>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.admin.name') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.character.account') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.character.faction') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.character.level') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.character.synced') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($characters as $c)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4">
                                <a href="{{ route('admin.characters.show', $c) }}" class="font-medium text-text hover:text-accent">{{ $c->name }}</a>
                                @if ($c->is_profile_hidden)<x-ui.badge tone="amber">{{ __('game.admin.hide_profile') }}</x-ui.badge>@endif
                            </td>
                            <td class="px-4 text-text-muted">{{ $c->account?->account_name }}</td>
                            <td class="px-4 text-text-muted">{{ $c->faction?->name ?? '—' }}</td>
                            <td class="px-4 text-text-muted">{{ $c->level ?? '—' }}</td>
                            <td class="px-4 text-text-muted">{{ $c->synced_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('admin.none') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $characters->links() }}</div>
</x-layouts.admin>
