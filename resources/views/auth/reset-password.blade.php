<x-layouts.auth :title="__('auth.ui.reset.title')">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-ui.field name="email" :label="__('auth.ui.forgot.email')" required>
            <x-ui.input name="email" type="email" :value="$request->query('email')" autocomplete="email" required />
        </x-ui.field>
        <x-ui.field name="password" :label="__('auth.ui.register.password')" :hint="__('auth.ui.register.password_hint')" required>
            <x-ui.input name="password" type="password" autocomplete="new-password" hint required minlength="10" autofocus />
        </x-ui.field>
        <x-ui.field name="password_confirmation" :label="__('auth.ui.register.password_confirmation')" required>
            <x-ui.input name="password_confirmation" type="password" autocomplete="new-password" required />
        </x-ui.field>
        <x-ui.button type="submit" class="w-full" data-submit-once>{{ __('auth.ui.reset.submit') }}</x-ui.button>
    </form>
</x-layouts.auth>
