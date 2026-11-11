{{-- 2FA: iki ayrı form (TOTP / kurtarma kodu). Geçiş native <details> ile, JS gerektirmez. --}}
<x-layouts.auth :title="__('auth.ui.two_factor.title')">
    <x-slot:intro>{{ __('auth.ui.two_factor.intro') }}</x-slot:intro>
    <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-5" novalidate>
        @csrf
        <x-ui.field name="code" :label="__('auth.ui.two_factor.code')" required>
            <x-ui.input name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" autofocus class="font-mono text-lg tracking-[0.4em]" />
        </x-ui.field>
        <x-ui.button type="submit" class="w-full" data-submit-once>{{ __('auth.ui.two_factor.submit') }}</x-ui.button>
    </form>
    <details class="mt-8 border-t border-border pt-6" @error('recovery_code') open @enderror>
        <summary class="cursor-pointer text-sm text-accent hover:underline">{{ __('auth.ui.two_factor.use_recovery') }}</summary>
        <p class="mt-3 text-sm text-text-muted">{{ __('auth.ui.two_factor.recovery_intro') }}</p>
        <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-4 space-y-4" novalidate>
            @csrf
            <x-ui.field name="recovery_code" :label="__('auth.ui.two_factor.recovery_code')" required>
                <x-ui.input name="recovery_code" autocomplete="one-time-code" maxlength="64" class="font-mono" />
            </x-ui.field>
            <x-ui.button type="submit" variant="secondary" class="w-full">{{ __('auth.ui.two_factor.submit') }}</x-ui.button>
        </form>
    </details>
</x-layouts.auth>
