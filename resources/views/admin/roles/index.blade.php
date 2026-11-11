<x-layouts.admin :title="__('admin.roles.title')">
    <x-admin.header :title="__('admin.roles.title')" :description="__('admin.roles.system_note')">
        @can('roles.manage')
            <x-slot:actions><x-ui.button size="sm" icon="plus" :href="route('admin.roles.create')">{{ __('admin.roles.create') }}</x-ui.button></x-slot:actions>
        @endcan
    </x-admin.header>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>@foreach (__('admin.roles.columns') as $col)<th scope="col" class="px-4 py-2.5 font-medium">{{ $col }}</th>@endforeach</tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach ($roles as $role)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="font-medium text-text hover:text-accent">{{ $role->name }}</a>
                                <span class="ml-2 font-mono text-xs text-text-subtle">{{ $role->slug }}</span>
                            </td>
                            <td class="px-4 font-mono tabular-nums text-text-muted">{{ $role->priority }}</td>
                            <td class="px-4 font-mono tabular-nums text-text-muted">{{ $role->users_count }}</td>
                            <td class="px-4 font-mono tabular-nums text-text-muted">{{ $role->isSuperAdmin() ? __('admin.roles.all_permissions') : $role->permissions_count }}</td>
                            <td class="px-4">
                                @if ($role->is_system)<x-ui.badge tone="info">{{ __('admin.roles.system') }}</x-ui.badge>
                                @elseif (! $role->is_staff)<x-ui.badge>{{ __('admin.roles.cosmetic') }}</x-ui.badge>
                                @else<x-ui.badge tone="accent">{{ __('admin.roles.custom') }}</x-ui.badge>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-admin.card>
</x-layouts.admin>
