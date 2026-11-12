{{-- Cezalar — docs/ROUTES.md §6.7. --}}
<x-layouts.admin :title="__('moderation.admin.sanctions')">
    <x-admin.header :title="__('moderation.admin.sanctions')" />
    @can('sanctions.create')<p class="mb-4"><x-ui.button size="sm" icon="plus" :href="route('admin.sanctions.create')">{{ __('moderation.admin.new') }}</x-ui.button></p>@endcan

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <div class="min-w-64 flex-1">
            <label for="q" class="sr-only">{{ __('admin.search') }}</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="{{ __('moderation.admin.user') }}" class="h-10 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
        </div>
        <label for="status" class="sr-only">{{ __('admin.filter') }}</label>
        <select id="status" name="status" class="h-10 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            <option value="all">{{ __('admin.all') }}</option>
            @foreach (__('moderation.status') as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
        </select>
        <x-ui.button type="submit" size="sm" variant="secondary" icon="search">{{ __('admin.filter') }}</x-ui.button>
    </form>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('moderation.admin.type') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('moderation.admin.target') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('moderation.reason') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('moderation.admin.issued_by') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('moderation.ends') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($sanctions as $s)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4"><a href="{{ route('admin.sanctions.show', $s) }}" class="font-medium text-text hover:text-accent">{{ __('moderation.types.'.$s->type) }}</a>
                                <x-ui.badge :tone="$s->status === 'active' ? 'danger' : 'neutral'">{{ __('moderation.status.'.$s->status) }}</x-ui.badge></td>
                            <td class="px-4 text-text-muted">{{ $s->user?->username ?? '—' }} @if ($s->account)· {{ $s->account->account_name }}@endif</td>
                            <td class="max-w-xs truncate px-4 text-text-muted">{{ $s->public_reason }}</td>
                            <td class="px-4 text-text-muted">{{ $s->issuer?->username }}</td>
                            <td class="px-4 text-text-muted">{{ $s->ends_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('moderation.admin.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $sanctions->links() }}</div>
</x-layouts.admin>
