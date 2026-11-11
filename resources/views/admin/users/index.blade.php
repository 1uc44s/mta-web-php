<x-layouts.admin :title="__('admin.users.title')">
    <x-admin.header :title="__('admin.users.title')" />

    <form method="GET" action="{{ route('admin.users.index') }}" role="search" class="mb-4 flex flex-wrap items-end gap-2">
        <div class="min-w-64 flex-1">
            <label for="q" class="sr-only">{{ __('admin.search') }}</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="{{ __('admin.users.search_placeholder') }}"
                   class="h-10 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text placeholder:text-text-subtle focus:border-accent focus:outline-none">
        </div>
        <label for="status" class="sr-only">{{ __('admin.users.columns.status') }}</label>
        <select id="status" name="status" class="h-10 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            <option value="">{{ __('admin.all') }}</option>
            @foreach (__('admin.users.status') as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-ui.button type="submit" size="sm" variant="secondary" icon="search">{{ __('admin.filter') }}</x-ui.button>
        @if ($q !== '' || $status !== '')
            <x-ui.button size="sm" variant="ghost" :href="route('admin.users.index')">{{ __('admin.clear') }}</x-ui.button>
        @endif
    </form>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>
                        @foreach (__('admin.users.columns') as $col)
                            <th scope="col" class="px-4 py-2.5 font-medium">{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($users as $u)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4"><a href="{{ route('admin.users.show', $u) }}" class="font-medium text-text hover:text-accent">{{ $u->username }}</a></td>
                            <td class="px-4 font-mono text-xs text-text-muted">{{ $canPii ? $u->email : \App\Support\EmailMask::mask($u->email) }}</td>
                            <td class="px-4">
                                <x-ui.badge :tone="match ($u->status->value) { 'active' => 'success', 'suspended' => 'danger', default => 'neutral' }">{{ __('admin.users.status.'.$u->status->value) }}</x-ui.badge>
                            </td>
                            <td class="px-4 text-text-muted">{{ $u->created_at?->format('d.m.Y') }}</td>
                            <td class="px-4 text-text-muted">{{ $u->last_login_at?->diffForHumans() ?? __('admin.none') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('admin.users.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.admin>
