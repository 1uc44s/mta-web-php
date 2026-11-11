<x-layouts.admin :title="__('admin.audit.title')">
    <x-admin.header :title="__('admin.audit.title')" />

    @if ($chain)
        <div role="status" @class([
            'mb-4 flex items-center gap-2 rounded-md border px-4 py-2.5',
            'border-success/40 text-text' => $chain['ok'],
            'border-danger/50 bg-danger/10 text-text' => ! $chain['ok'],
        ])>
            @if ($chain['ok'])
                <x-lucide-link class="size-4 text-success" aria-hidden="true" />{{ __('admin.audit.chain_ok', ['count' => $chain['count']]) }}
            @else
                <x-lucide-unlink class="size-4 text-danger" aria-hidden="true" />{{ __('admin.audit.chain_broken', ['id' => $chain['broken']]) }}
            @endif
        </div>
    @endif

    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="mb-4 flex flex-wrap items-end gap-2">
        <label for="action" class="sr-only">{{ __('admin.audit.action_filter') }}</label>
        <input id="action" name="action" value="{{ $action }}" placeholder="{{ __('admin.audit.action_filter') }}" class="h-10 min-w-56 rounded-md border border-border-input bg-surface-1 px-3 font-mono text-xs text-text placeholder:text-text-subtle">
        <label for="severity" class="sr-only">{{ __('admin.audit.columns.severity') }}</label>
        <select id="severity" name="severity" class="h-10 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            <option value="">{{ __('admin.all') }}</option>
            @foreach (__('admin.audit.severity') as $value => $label)<option value="{{ $value }}" @selected($severity === $value)>{{ $label }}</option>@endforeach
        </select>
        <x-ui.button type="submit" size="sm" variant="secondary" icon="filter">{{ __('admin.filter') }}</x-ui.button>
    </form>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>@foreach (__('admin.audit.columns') as $col)<th scope="col" class="px-4 py-2.5 font-medium">{{ $col }}</th>@endforeach</tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($logs as $log)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4 whitespace-nowrap text-text-muted"><time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d.m.Y H:i:s') }}</time></td>
                            <td class="px-4 text-text">{{ $log->actor?->username ?? __('admin.audit.actor_types.'.$log->actor_type) }}</td>
                            <td class="px-4"><a href="{{ route('admin.audit-logs.show', $log->ulid) }}" class="font-mono text-xs text-text hover:text-accent">{{ $log->action }}</a></td>
                            <td class="px-4 font-mono text-xs text-text-muted">{{ $log->target_type ? $log->target_type.'#'.$log->target_id : __('admin.none') }}</td>
                            <td class="px-4"><x-ui.badge :tone="match ($log->severity) { 'critical' => 'danger', 'warning' => 'amber', default => 'neutral' }">{{ __('admin.audit.severity.'.$log->severity) }}</x-ui.badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('admin.audit.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.admin>
