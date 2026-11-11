<x-layouts.auth :title="__('auth.ui.confirm.title')">
    <x-slot:intro>{{ __('auth.ui.confirm.intro') }}</x-slot:intro>
    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5" novalidate>
        @csrf
        <x-ui.field name="password" :label="__('auth.ui.login.password')" required>
            <x-ui.input name="password" type="password" autocomplete="current-password" required autofocus />
        </x-ui.field>
        <x-ui.button type="submit" class="w-full" data-submit-once>{{ __('auth.ui.confirm.submit') }}</x-ui.button>
    </form>
</x-layouts.auth>
