<x-layouts.admin :title="__('admin.activity.title')">
    <x-admin.header :title="__('admin.activity.title')" />
    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>@foreach (__('admin.activity.columns') as $col)<th scope="col" class="px-4 py-2.5 font-medium">{{ $col }}</th>@endforeach</tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($logs as $log)
                        <tr class="h-11">
                            <td class="px-4 whitespace-nowrap text-text-muted">{{ $log->created_at->format('d.m.Y H:i:s') }}</td>
                            <td class="px-4 text-text">{{ $log->user?->username }}</td>
                            <td class="px-4 font-mono text-xs text-text">{{ $log->route_name }}</td>
                            <td class="px-4 font-mono text-xs text-text-muted">{{ $log->method }}</td>
                            <td class="px-4 font-mono text-xs text-text-muted">{{ $log->target_type ? $log->target_type.'#'.$log->target_id : __('admin.none') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('admin.activity.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.admin>
