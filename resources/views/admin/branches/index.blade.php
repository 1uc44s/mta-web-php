<x-layouts.admin :title="__('admin.branches.title')">
    <x-admin.header :title="__('admin.branches.title')">
        @can('branches.manage')
            <x-slot:actions><x-ui.button size="sm" icon="plus" :href="route('admin.branches.create')">{{ __('admin.branches.create') }}</x-ui.button></x-slot:actions>
        @endcan
    </x-admin.header>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>@foreach (__('admin.branches.columns') as $col)<th scope="col" class="px-4 py-2.5 font-medium">{{ $col }}</th>@endforeach</tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($branches as $branch)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4">
                                <a href="{{ route('admin.branches.show', $branch) }}" class="font-medium text-text hover:text-accent">{{ $branch->name }}</a>
                                <span class="ml-2 font-mono text-xs text-text-subtle">{{ $branch->slug }}</span>
                            </td>
                            <td class="px-4 text-text-muted">{{ __('admin.branches.types.'.$branch->type) }}</td>
                            <td class="px-4 font-mono tabular-nums text-text-muted">{{ $branch->members_count }}</td>
                            <td class="px-4 text-text-muted">{{ $branch->members->pluck('username')->implode(', ') ?: __('admin.none') }}</td>
                            <td class="px-4">@if ($branch->is_active)<x-ui.badge tone="success">{{ __('admin.branches.active') }}</x-ui.badge>@else<x-ui.badge>{{ __('admin.branches.inactive') }}</x-ui.badge>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.card>
</x-layouts.admin>
