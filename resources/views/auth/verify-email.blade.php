<x-layouts.auth :title="__('auth.ui.verify.title')">
    <p class="text-text-muted">{{ __('auth.ui.verify.text') }}</p>
    <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
        @csrf
        <x-ui.button type="submit" class="w-full" icon="send" data-submit-once>{{ __('auth.ui.verify.resend') }}</x-ui.button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <x-ui.button type="submit" variant="ghost" class="w-full">Çıkış yap</x-ui.button>
    </form>
</x-layouts.auth>
