<x-layouts.admin :title="__('admin.staff.title')">
    <x-admin.header :title="__('admin.staff.title')" />

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>@foreach (__('admin.staff.columns') as $col)<th scope="col" class="px-4 py-2.5 font-medium">{{ $col }}</th>@endforeach</tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($staff as $member)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4"><a href="{{ route('admin.staff.show', $member) }}" class="font-medium text-text hover:text-accent">{{ $member->username }}</a></td>
                            <td class="px-4"><div class="flex flex-wrap gap-1">@foreach ($member->roles as $role)<x-ui.badge tone="accent">{{ $role->name }}</x-ui.badge>@endforeach</div></td>
                            <td class="px-4 text-text-muted">
                                @if ($member->branches->isEmpty())
                                    <span class="inline-flex items-center gap-1 text-amber"><x-lucide-triangle-alert class="size-3.5" aria-hidden="true" />{{ __('admin.staff.no_branch') }}</span>
                                @else
                                    {{ $member->branches->pluck('name')->implode(', ') }}
                                @endif
                            </td>
                            <td class="px-4">@if ($member->hasTwoFactorEnabled())<x-ui.badge tone="success" icon="lock">{{ __('admin.staff.mfa_on') }}</x-ui.badge>@else<x-ui.badge tone="danger">{{ __('admin.staff.mfa_off') }}</x-ui.badge>@endif</td>
                            <td class="px-4 text-text-muted">{{ $member->last_login_at?->diffForHumans() ?? __('admin.none') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('admin.staff.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $staff->links() }}</div>
</x-layouts.admin>
