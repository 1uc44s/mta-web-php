<x-layouts.admin :title="$user->username">
    <x-admin.header :title="$user->username" :back="route('admin.users.index')" :back-label="__('admin.users.title')">
        <x-slot:actions>
            <x-ui.badge :tone="match ($user->status->value) { 'active' => 'success', 'suspended' => 'danger', default => 'neutral' }">{{ __('admin.users.status.'.$user->status->value) }}</x-ui.badge>
            @can('users.security_read')
                <x-ui.button size="sm" variant="secondary" icon="shield" :href="route('admin.users.security', $user)">{{ __('admin.users.tabs.security') }}</x-ui.button>
            @endcan
            @if ($user->roles->where('is_staff', true)->isNotEmpty())
                @can('staff.read')
                    <x-ui.button size="sm" variant="secondary" icon="shield-user" :href="route('admin.staff.show', $user)">{{ __('admin.nav.staff') }}</x-ui.button>
                @endcan
            @endif
        </x-slot:actions>
    </x-admin.header>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <x-admin.card :title="__('admin.users.tabs.overview')">
                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div><dt class="text-xs text-text-subtle">E-posta</dt>
                        <dd class="mt-1 font-mono text-xs text-text">{{ $canPii ? $user->email : \App\Support\EmailMask::mask($user->email) }}
                            @unless ($canPii)<span class="mt-1 block font-sans text-[11px] text-text-subtle">{{ __('admin.users.pii_hidden') }}</span>@endunless
                        </dd></div>
                    <div><dt class="text-xs text-text-subtle">ULID</dt><dd class="mt-1 font-mono text-xs text-text-muted">{{ $user->ulid }}</dd></div>
                    <div><dt class="text-xs text-text-subtle">E-posta doğrulama</dt>
                        <dd class="mt-1">@if ($user->hasVerifiedEmail())<x-ui.badge tone="success" icon="check">{{ __('admin.users.verified') }}</x-ui.badge>@else<x-ui.badge tone="amber">{{ __('admin.users.unverified') }}</x-ui.badge>@endif</dd></div>
                    <div><dt class="text-xs text-text-subtle">{{ __('admin.users.two_factor') }}</dt>
                        <dd class="mt-1">@if ($user->hasTwoFactorEnabled())<x-ui.badge tone="success" icon="lock">{{ __('admin.staff.mfa_on') }}</x-ui.badge>@else<x-ui.badge>{{ __('admin.staff.mfa_off') }}</x-ui.badge>@endif</dd></div>
                    <div><dt class="text-xs text-text-subtle">{{ __('admin.users.columns.registered') }}</dt><dd class="mt-1 text-text">{{ $user->created_at?->format('d.m.Y H:i') }}</dd></div>
                    <div><dt class="text-xs text-text-subtle">{{ __('admin.users.columns.last_login') }}</dt><dd class="mt-1 text-text">{{ $user->last_login_at?->format('d.m.Y H:i') ?? __('admin.none') }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-text-subtle">{{ __('admin.users.roles') }}</dt>
                        <dd class="mt-1 flex flex-wrap gap-1.5">
                            @forelse ($user->roles as $role)<x-ui.badge tone="accent">{{ $role->name }}</x-ui.badge>@empty<span class="text-text-muted">{{ __('admin.none') }}</span>@endforelse
                        </dd></div>
                    @if ($user->status->value === 'suspended')
                        <div class="sm:col-span-2"><dt class="text-xs text-text-subtle">{{ __('admin.users.suspension') }}</dt>
                            <dd class="mt-1 text-text">{{ $user->suspension_reason }} · {{ $user->suspended_until ? __('admin.users.suspended_until', ['date' => $user->suspended_until->format('d.m.Y H:i')]) : __('admin.users.indefinite') }}</dd></div>
                    @endif
                </dl>
            </x-admin.card>

            @if ($assignable->isNotEmpty())
                <x-admin.card :title="__('admin.staff.assign')">
                    <p class="mb-4 text-text-muted">{{ __('admin.staff.assign_hint') }}</p>
                    @include('admin.staff.partials.assign-form', ['target' => $user])
                </x-admin.card>
            @endif
        </div>

        <div class="space-y-6">
            @can('users.suspend')
                <x-admin.card :title="$user->status->value === 'suspended' ? __('admin.users.unsuspend') : __('admin.users.suspend')">
                    <form method="POST" action="{{ route('admin.users.status', $user) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="action" value="{{ $user->status->value === 'suspended' ? 'unsuspend' : 'suspend' }}">
                        @if ($user->status->value !== 'suspended')
                            <x-ui.field name="until" :label="__('admin.users.suspend_until')">
                                <x-ui.input name="until" type="datetime-local" />
                            </x-ui.field>
                        @endif
                        <x-ui.field name="reason" :label="__('admin.reason')" :hint="__('admin.reason_hint')" required>
                            <x-ui.input name="reason" multiline rows="3" maxlength="500" required />
                        </x-ui.field>
                        <x-ui.button type="submit" size="sm" :variant="$user->status->value === 'suspended' ? 'secondary' : 'danger'" class="w-full">
                            {{ $user->status->value === 'suspended' ? __('admin.users.unsuspend') : __('admin.users.suspend') }}
                        </x-ui.button>
                    </form>
                </x-admin.card>
            @endcan

            @if (! $user->hasVerifiedEmail())
                @can('users.update')
                    <form method="POST" action="{{ route('admin.users.resend-verification', $user) }}">
                        @csrf
                        <x-ui.button type="submit" size="sm" variant="secondary" icon="mail" class="w-full">{{ __('admin.users.resend_verification') }}</x-ui.button>
                    </form>
                @endcan
            @endif
        </div>
    </div>
</x-layouts.admin>
