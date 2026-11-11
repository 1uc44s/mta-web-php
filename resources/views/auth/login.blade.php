<x-layouts.auth :title="__('auth.ui.login.title')">
    <x-slot:intro>{{ __('auth.ui.login.intro') }}</x-slot:intro>
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5" novalidate>
        @csrf
        <x-ui.field name="login" :label="__('auth.ui.login.login')" required>
            <x-ui.input name="login" autocomplete="username" required autofocus maxlength="254" />
        </x-ui.field>
        <x-ui.field name="password" :label="__('auth.ui.login.password')" required>
            <x-ui.input name="password" type="password" autocomplete="current-password" required />
        </x-ui.field>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-text-muted">
                <input type="checkbox" name="remember" value="1" class="size-4 accent-[var(--c-accent)]" @checked(old('remember'))>
                {{ __('auth.ui.login.remember') }}
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-accent hover:underline">{{ __('auth.ui.login.forgot') }}</a>
        </div>
        <x-ui.button type="submit" class="w-full" data-submit-once>{{ __('auth.ui.login.submit') }}</x-ui.button>
    </form>
    <p class="mt-8 border-t border-border pt-6 text-center text-sm text-text-muted">
        {{ __('auth.ui.login.no_account') }} <a href="{{ route('register') }}" class="font-medium text-accent hover:underline">{{ __('auth.ui.login.register') }}</a>
    </p>
</x-layouts.auth>
