<x-layouts.auth :title="__('auth.ui.forgot.title')">
    <x-slot:intro>{{ __('auth.ui.forgot.intro') }}</x-slot:intro>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" novalidate>
        @csrf
        <x-ui.field name="email" :label="__('auth.ui.forgot.email')" required>
            <x-ui.input name="email" type="email" autocomplete="email" required autofocus maxlength="254" />
        </x-ui.field>
        <x-ui.button type="submit" class="w-full" data-submit-once>{{ __('auth.ui.forgot.submit') }}</x-ui.button>
    </form>
    <a href="{{ route('login') }}" class="mt-6 inline-flex items-center gap-1.5 text-sm text-text-muted hover:text-text">
        <x-lucide-arrow-left class="size-4" aria-hidden="true" />{{ __('auth.ui.forgot.back') }}
    </a>
</x-layouts.auth>
