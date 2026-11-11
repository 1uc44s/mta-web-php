<x-layouts.admin :title="$log->action">
    <x-admin.header :title="$log->action" :description="__('admin.audit.detail')" :back="route('admin.audit-logs.index')" :back-label="__('admin.audit.title')">
        <x-slot:actions>
            <x-ui.badge :tone="match ($log->severity) { 'critical' => 'danger', 'warning' => 'amber', default => 'neutral' }">{{ __('admin.audit.severity.'.$log->severity) }}</x-ui.badge>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
        <x-admin.card :title="__('admin.audit.changes')">
            @if ($log->reason)
                <p class="mb-4 rounded-md border border-border bg-surface-2 px-3 py-2 text-text"><span class="text-xs text-text-subtle">{{ __('admin.reason') }}:</span> {{ $log->reason }}</p>
            @endif
            <pre class="overflow-x-auto rounded-md border border-border bg-bg p-4 font-mono text-xs leading-relaxed text-text">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—' }}</pre>
        </x-admin.card>

        <x-admin.card>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-xs text-text-subtle">{{ __('admin.audit.columns.time') }}</dt><dd class="text-text">{{ $log->created_at->format('d.m.Y H:i:s') }} UTC</dd></div>
                <div><dt class="text-xs text-text-subtle">{{ __('admin.audit.columns.actor') }}</dt><dd class="text-text">{{ $log->actor?->username ?? __('admin.audit.actor_types.'.$log->actor_type) }}</dd></div>
                <div><dt class="text-xs text-text-subtle">{{ __('admin.audit.columns.target') }}</dt><dd class="font-mono text-xs text-text">{{ $log->target_type ? $log->target_type.'#'.$log->target_id : __('admin.none') }}</dd></div>
                <div><dt class="text-xs text-text-subtle">IP</dt><dd class="font-mono text-xs text-text-muted">{{ $log->ip_masked ?? __('admin.none') }} · {{ $log->user_agent_summary ?? '' }}</dd></div>
                <div><dt class="text-xs text-text-subtle">Request ID</dt><dd class="font-mono text-xs text-text-muted">{{ $log->request_id ?? __('admin.none') }}</dd></div>
                <div><dt class="text-xs text-text-subtle">{{ __('admin.audit.chain') }}</dt>
                    <dd class="font-mono text-[11px] break-all text-text-muted">prev {{ $log->prev_hash ?? '∅' }}<br>hash {{ $log->hash }}</dd></div>
            </dl>
        </x-admin.card>
    </div>
</x-layouts.admin>
