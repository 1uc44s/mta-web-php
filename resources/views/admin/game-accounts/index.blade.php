{{-- Oyun hesapları — docs/ROUTES.md §6.6. --}}
<x-layouts.admin :title="__('game.admin.accounts')">
    <x-admin.header :title="__('game.admin.accounts')" />

    <form method="GET" action="{{ route('admin.game-accounts.index') }}" role="search" class="mb-4 flex flex-wrap items-end gap-2">
        <div class="min-w-64 flex-1">
            <label for="q" class="sr-only">{{ __('admin.search') }}</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="{{ __('game.admin.search') }}"
                   class="h-10 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text placeholder:text-text-subtle focus:border-accent focus:outline-none">
        </div>
        <label for="filter" class="sr-only">{{ __('admin.filter') }}</label>
        <select id="filter" name="filter" class="h-10 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            @foreach (__('game.admin.filter') as $value => $label)
                <option value="{{ $value }}" @selected($filter === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-ui.button type="submit" size="sm" variant="secondary" icon="search">{{ __('admin.filter') }}</x-ui.button>
    </form>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.admin.name') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.admin.ref') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.admin.server') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.admin.user') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.link.last_seen') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($accounts as $a)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4"><a href="{{ route('admin.game-accounts.show', $a) }}" class="font-medium text-text hover:text-accent">{{ $a->account_name }}</a></td>
                            <td class="px-4 font-mono text-xs text-text-muted">{{ $a->mta_account_ref }}</td>
                            <td class="px-4 text-text-muted">{{ $a->server?->name }}</td>
                            <td class="px-4">
                                @if ($a->activeLink)
                                    <span class="text-text">{{ $a->activeLink->user?->username }}</span>
                                    @if ($a->activeLink->status === 'unlink_requested')<x-ui.badge tone="amber">{{ __('game.link.status.unlink_requested') }}</x-ui.badge>@endif
                                @else
                                    <span class="text-text-subtle">—</span>
                                @endif
                            </td>
                            <td class="px-4 text-text-muted">{{ $a->last_seen_at?->diffForHumans() ?? __('game.admin.never') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('admin.none') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $accounts->links() }}</div>

    @can('game_accounts.manage')
        <x-admin.card :title="__('game.admin.manual_link')" class="mt-8">
            <p class="mb-4 text-sm text-text-muted">{{ __('game.admin.manual_hint') }}</p>
            <form method="POST" action="{{ route('admin.game-accounts.manual-link') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <x-ui.field name="username" :label="__('game.admin.username')" required><x-ui.input name="username" required /></x-ui.field>
                <x-ui.field name="mta_server_id" :label="__('game.admin.server')" required>
                    <select id="f-mta_server_id" name="mta_server_id" class="h-10 rounded-sm border border-border-input bg-surface-2 px-3 text-sm text-text" required>
                        @foreach ($servers as $s)<option value="{{ $s->id }}" @selected(old('mta_server_id') == $s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </x-ui.field>
                <x-ui.field name="mta_account_ref" :label="__('game.admin.ref')" required><x-ui.input name="mta_account_ref" required /></x-ui.field>
                <x-ui.field name="account_name" :label="__('game.admin.name')" required><x-ui.input name="account_name" required /></x-ui.field>
                <x-ui.field name="evidence" :label="__('game.admin.evidence')" class="sm:col-span-2" required><x-ui.input name="evidence" multiline rows="2" required /></x-ui.field>
                <x-ui.field name="reason" :label="__('admin.reason')" class="sm:col-span-2" required><x-ui.input name="reason" multiline rows="2" required /></x-ui.field>
                <div class="sm:col-span-2"><x-ui.button type="submit" size="sm" icon="link">{{ __('game.admin.manual_link') }}</x-ui.button></div>
            </form>
        </x-admin.card>
    @endcan
</x-layouts.admin>
