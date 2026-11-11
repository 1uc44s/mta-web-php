<x-layouts.admin :title="$user->username">
    <x-admin.header :title="$user->username" :back="route('admin.staff.index')" :back-label="__('admin.staff.title')">
        <x-slot:actions>
            @if ($user->hasTwoFactorEnabled())<x-ui.badge tone="success" icon="lock">2FA {{ __('admin.staff.mfa_on') }}</x-ui.badge>@else<x-ui.badge tone="danger">2FA {{ __('admin.staff.mfa_off') }}</x-ui.badge>@endif
            @can('users.read')<x-ui.button size="sm" variant="secondary" icon="user" :href="route('admin.users.show', $user)">{{ __('admin.nav.users') }}</x-ui.button>@endcan
        </x-slot:actions>
    </x-admin.header>

    @if ($user->branches->isEmpty())
        <div role="status" class="mb-6 flex items-start gap-2 rounded-md border border-amber/40 bg-amber-soft px-4 py-3 text-text">
            <x-lucide-triangle-alert class="mt-0.5 size-4 shrink-0 text-amber" aria-hidden="true" />{{ __('admin.staff.no_branch_warning') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <x-admin.card :title="__('admin.users.roles')" flush>
                <ul class="divide-y divide-border">
                    @foreach ($user->roles as $role)
                        <li class="flex flex-wrap items-center gap-3 px-4 py-3">
                            <x-ui.badge tone="accent">{{ $role->name }}</x-ui.badge>
                            <span class="font-mono text-xs text-text-subtle">{{ $role->slug }} · {{ $role->priority }}</span>
                            @if ($role->pivot->expires_at)
                                <span class="text-xs text-amber">{{ __('admin.expires_at') }}: {{ \Illuminate\Support\Carbon::parse($role->pivot->expires_at)->format('d.m.Y H:i') }}</span>
                            @endif
                            @can('staff.manage')
                                <details class="ml-auto w-full sm:w-auto">
                                    <summary class="cursor-pointer text-xs text-danger hover:underline">{{ __('admin.staff.revoke') }}</summary>
                                    <form method="POST" action="{{ route('admin.staff.roles.revoke', [$user, $role]) }}" class="mt-3 space-y-3 sm:w-80">
                                        @csrf
                                        <x-ui.field :name="'reason'" :label="__('admin.reason')" required>
                                            <x-ui.input name="reason" multiline rows="2" maxlength="1000" required />
                                        </x-ui.field>
                                        <x-ui.button type="submit" size="sm" variant="danger">{{ __('admin.staff.revoke_role', ['role' => $role->name]) }}</x-ui.button>
                                    </form>
                                </details>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            </x-admin.card>

            @can('staff.manage')
                @if ($assignable->isNotEmpty())
                    <x-admin.card :title="__('admin.staff.assign')">
                        <p class="mb-4 text-text-muted">{{ __('admin.staff.assign_hint') }}</p>
                        @include('admin.staff.partials.assign-form', ['target' => $user])
                    </x-admin.card>
                @endif
            @endcan

            @if ($history->isNotEmpty())
                <x-admin.card :title="__('admin.audit.title')" flush>
                    <ul class="divide-y divide-border">
                        @foreach ($history as $log)
                            <li class="flex flex-wrap items-center gap-3 px-4 py-2.5">
                                <a href="{{ route('admin.audit-logs.show', $log->ulid) }}" class="font-mono text-xs text-text hover:text-accent">{{ $log->action }}</a>
                                <span class="text-text-muted">{{ $log->reason }}</span>
                                <time class="ml-auto text-xs text-text-subtle" datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d.m.Y H:i') }}</time>
                            </li>
                        @endforeach
                    </ul>
                </x-admin.card>
            @endif
        </div>

        <x-admin.card :title="__('admin.nav.branches')">
            <ul class="space-y-2">
                @forelse ($user->branches as $branch)
                    <li class="flex items-center justify-between gap-2">
                        <a href="{{ route('admin.branches.show', $branch) }}" class="text-text hover:text-accent">{{ $branch->name }}</a>
                        @if ($branch->pivot->is_lead)<x-ui.badge tone="amber">{{ __('admin.branches.lead') }}</x-ui.badge>@endif
                    </li>
                @empty
                    <li class="text-text-muted">{{ __('admin.staff.no_branch') }}</li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>
</x-layouts.admin>
