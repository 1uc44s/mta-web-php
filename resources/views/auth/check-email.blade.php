{{-- Kayıt sonrası her durumda gösterilen ekran (SEC-005): hesap varlığı ifşa edilmez. --}}
<x-layouts.auth :title="__('auth.ui.check_email.title')">
    <span class="grid size-14 place-items-center rounded-full border border-accent/40 bg-accent-soft text-accent">
        <x-lucide-mail-check class="size-6" aria-hidden="true" />
    </span>
    <p class="mt-6 text-text-muted">
        {{ $email ? __('auth.ui.check_email.text', ['email' => $email]) : __('auth.ui.check_email.text_generic') }}
    </p>
    <p class="mt-3 text-sm text-text-subtle">{{ __('auth.ui.check_email.spam') }}</p>
    <x-ui.button :href="route('login')" variant="secondary" class="mt-8 w-full">{{ __('auth.ui.check_email.login') }}</x-ui.button>
</x-layouts.auth>
