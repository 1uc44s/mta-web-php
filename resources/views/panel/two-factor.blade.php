{{-- 2FA kurulum sihirbazı — docs/ROUTES.md §5 (panel.security.two-factor), SRS FR-AUTH-016/017. Fortify rotalarını kullanır. --}}
<x-layouts.public :title="__('auth.ui.two_factor_setup.title')">
    <section class="mx-auto max-w-3xl px-4 py-14 sm:px-8">
        <x-brand.eyebrow>{{ __('auth.ui.two_factor_setup.eyebrow') }}</x-brand.eyebrow>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('auth.ui.two_factor_setup.title') }}</h1>
            @if ($confirmed)<x-ui.badge tone="success" icon="lock">{{ __('auth.ui.two_factor_setup.status_on') }}</x-ui.badge>
            @elseif ($pending)<x-ui.badge tone="amber">{{ __('auth.ui.two_factor_setup.status_pending') }}</x-ui.badge>
            @else<x-ui.badge>{{ __('auth.ui.two_factor_setup.status_off') }}</x-ui.badge>@endif
        </div>
        <p class="mt-4 max-w-2xl text-text-muted">{{ __('auth.ui.two_factor_setup.intro') }}</p>
        @if ($isStaff)
            <p class="mt-3 flex items-start gap-2 rounded-md border border-amber/40 bg-amber-soft px-4 py-3 text-sm text-text">
                <x-lucide-shield-alert class="mt-0.5 size-4 shrink-0 text-amber" aria-hidden="true" />{{ __('auth.ui.two_factor_setup.staff_note') }}
            </p>
        @endif

        @error('two_factor')<p role="alert" class="mt-4 text-sm text-danger">{{ $message }}</p>@enderror

        <div class="mt-8 rounded-lg border border-border bg-surface-1 p-6">
            @if (! $pending && ! $confirmed)
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <x-ui.button type="submit" icon="shield-check">{{ __('auth.ui.two_factor_setup.enable') }}</x-ui.button>
                </form>
            @elseif ($pending)
                <div class="grid gap-8 md:grid-cols-[auto_1fr]">
                    <div>
                        <h2 class="text-sm font-semibold text-text">{{ __('auth.ui.two_factor_setup.step_scan') }}</h2>
                        <img src="{{ $qr }}" alt="{{ __('auth.ui.two_factor_setup.scan_alt') }}" width="192" height="192" class="mt-3 size-48 rounded-md bg-white p-2">
                        <p class="mt-3 text-xs text-text-muted">{{ __('auth.ui.two_factor_setup.manual') }}</p>
                        <code class="mt-1 block font-mono text-sm tracking-wider break-all text-text">{{ $secret }}</code>
                    </div>
                    <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-4">
                        @csrf
                        <h2 class="text-sm font-semibold text-text">{{ __('auth.ui.two_factor_setup.step_confirm') }}</h2>
                        <x-ui.field name="code" :label="__('auth.ui.two_factor.code')" required>
                            <x-ui.input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required class="font-mono text-lg tracking-[0.4em]" />
                        </x-ui.field>
                        <x-ui.button type="submit" icon="check">{{ __('auth.ui.two_factor_setup.confirm') }}</x-ui.button>
                    </form>
                </div>
            @else
                <p class="flex items-center gap-2 text-text"><x-lucide-shield-check class="size-5 text-success" aria-hidden="true" />{{ __('auth.ui.two_factor_setup.enabled_text') }}</p>

                @if ($recoveryCodes !== [])
                    <div class="mt-6">
                        <h2 class="text-sm font-semibold text-text">{{ __('auth.ui.two_factor_setup.recovery_title') }}</h2>
                        <p class="mt-1 text-sm text-text-muted">{{ __('auth.ui.two_factor_setup.recovery_intro') }}</p>
                        <ul class="mt-3 grid grid-cols-1 gap-1.5 rounded-md border border-border bg-bg p-4 font-mono text-sm text-text sm:grid-cols-2">
                            @foreach ($recoveryCodes as $code)<li>{{ $code }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}">
                        @csrf
                        <x-ui.button type="submit" size="sm" variant="secondary" icon="refresh-cw">{{ __('auth.ui.two_factor_setup.regenerate') }}</x-ui.button>
                    </form>
                    @if ($isStaff)
                        <x-ui.button size="sm" icon="shield" :href="route('admin.dashboard')">{{ __('auth.ui.two_factor_setup.go_admin') }}</x-ui.button>
                    @else
                        <form method="POST" action="{{ route('two-factor.disable') }}">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" size="sm" variant="ghost">{{ __('auth.ui.two_factor_setup.disable') }}</x-ui.button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
