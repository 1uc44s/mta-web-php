<x-layouts.admin :title="__('admin.dashboard.title')">
    <x-admin.header :title="__('admin.dashboard.greeting', ['name' => $user->username])" />

    @if ($singleSuperAdmin)
        <div role="status" class="mb-6 flex items-start gap-2 rounded-md border border-amber/40 bg-amber-soft px-4 py-3 text-text">
            <x-lucide-triangle-alert class="mt-0.5 size-4 shrink-0 text-amber" aria-hidden="true" />
            {{ __('admin.dashboard.single_super_admin') }}
        </div>
    @endif

    @if ($kpis !== [])
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
            @php $icons = ['users' => 'users', 'new_users' => 'user-plus', 'unverified' => 'mail-question', 'suspended' => 'user-x', 'staff' => 'shield-user', 'contact' => 'inbox']; @endphp
            @foreach ($kpis as $key => $value)
                <x-admin.kpi :label="__('admin.dashboard.kpi.'.$key)" :value="$value" :icon="$icons[$key] ?? 'hash'" />
            @endforeach
        </div>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[2fr_1fr]">
        <div>
            @if ($recentAudit->isNotEmpty())
                <x-admin.card :title="__('admin.dashboard.recent_audit')" flush>
                    <ul class="divide-y divide-border">
                        @foreach ($recentAudit as $log)
                            <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-4 py-2.5">
                                <x-ui.badge :tone="$log->severity === 'critical' ? 'danger' : 'amber'">{{ __('admin.audit.severity.'.$log->severity) }}</x-ui.badge>
                                <a href="{{ route('admin.audit-logs.show', $log->ulid) }}" class="font-mono text-xs text-text hover:text-accent">{{ $log->action }}</a>
                                <span class="text-text-muted">{{ $log->actor?->username ?? __('admin.audit.actor_types.'.$log->actor_type) }}</span>
                                <time datetime="{{ $log->created_at->toIso8601String() }}" class="ml-auto text-xs text-text-subtle">{{ $log->created_at->diffForHumans() }}</time>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endif
        </div>

        <x-admin.card :title="__('admin.dashboard.my_permissions')">
            <dl class="space-y-3">
                <div>
                    <dt class="text-xs text-text-subtle">{{ __('admin.dashboard.roles') }}</dt>
                    <dd class="mt-1 flex flex-wrap gap-1.5">
                        @foreach ($user->authz()['roles'] as $slug)
                            <x-ui.badge tone="accent">{{ config('roles.'.$slug.'.name', $slug) }}</x-ui.badge>
                        @endforeach
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-text-subtle">{{ __('admin.dashboard.branches') }}</dt>
                    <dd class="mt-1 text-text">{{ $branches->pluck('name')->implode(', ') ?: __('admin.staff.no_branch') }}</dd>
                </div>
            </dl>
        </x-admin.card>
    </div>
</x-layouts.admin>
