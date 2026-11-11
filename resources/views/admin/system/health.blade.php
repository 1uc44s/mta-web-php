<x-layouts.admin :title="__('admin.health.title')">
    <x-admin.header :title="__('admin.health.title')" />

    @if ($debug)
        <div role="status" class="mb-6 flex items-start gap-2 rounded-md border border-amber/40 bg-amber-soft px-4 py-3 text-text">
            <x-lucide-bug class="mt-0.5 size-4 shrink-0 text-amber" aria-hidden="true" />{{ __('admin.health.debug_on_warning') }}
        </div>
    @endif

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-admin.kpi :label="__('admin.health.pending_jobs')" :value="$pendingJobs" icon="list-todo" />
        <x-admin.kpi :label="__('admin.health.failed_jobs')" :value="$failedJobs->count()" icon="circle-x" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <x-admin.card :title="__('admin.health.environment')">
            <dl class="grid grid-cols-[10rem_1fr] gap-y-2">
                @foreach ($info as $key => $value)
                    <dt class="text-text-subtle">{{ __('admin.health.'.$key) }}</dt><dd class="font-mono text-xs text-text">{{ $value }}</dd>
                @endforeach
                @if ($chainBroken !== false)
                    <dt class="text-text-subtle">{{ __('admin.health.audit_chain') }}</dt>
                    <dd>@if ($chainBroken === null)<x-ui.badge tone="success" icon="link">OK</x-ui.badge>@else<x-ui.badge tone="danger">#{{ $chainBroken }}</x-ui.badge>@endif</dd>
                @endif
            </dl>
        </x-admin.card>

        <x-admin.card :title="__('admin.health.failed_jobs')" flush>
            <ul class="divide-y divide-border">
                @forelse ($failedJobs as $job)
                    <li class="flex flex-wrap items-center gap-3 px-4 py-2.5">
                        <span class="font-mono text-xs text-text">{{ $job->queue }}</span>
                        <span class="min-w-0 flex-1 truncate text-xs text-text-muted">{{ \Illuminate\Support\Str::limit(strtok((string) $job->exception, "\n"), 120) }}</span>
                        <span class="text-xs text-text-subtle">{{ $job->failed_at }}</span>
                        @can('settings.update')
                            <form method="POST" action="{{ route('admin.system.failed-jobs.retry', $job->uuid) }}">@csrf
                                <button type="submit" class="text-xs text-accent hover:underline">{{ __('admin.health.retry') }}</button>
                            </form>
                        @endcan
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-text-muted">{{ __('admin.health.failed_empty') }}</li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>
</x-layouts.admin>
