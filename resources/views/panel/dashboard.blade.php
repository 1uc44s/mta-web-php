{{-- Faz 3 panel iskeleti. Tam panel düzeni (sidebar, kartlar) Faz 6'da (docs/DESIGN.md §7.2). --}}
<x-layouts.public :title="'Oyuncu paneli'">
    <section class="mx-auto max-w-[1240px] px-4 py-14 sm:px-8">
        <x-brand.eyebrow>Oyuncu paneli</x-brand.eyebrow>
        <h1 class="mt-4 font-display text-h1 font-bold font-expanded text-text">Hoş geldin, {{ $user->username }}</h1>

        @unless ($user->hasVerifiedEmail())
            <div class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-md border border-amber/40 bg-amber-soft px-5 py-4">
                <p class="flex items-start gap-2 text-sm text-text">
                    <x-lucide-mail-warning class="mt-0.5 size-4 shrink-0 text-amber" aria-hidden="true" />
                    {{ __('auth.ui.verify.text') }}
                </p>
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <x-ui.button type="submit" size="sm" variant="secondary">{{ __('auth.ui.verify.resend') }}</x-ui.button>
                </form>
            </div>
        @endunless

        <p class="mt-8 max-w-xl text-text-muted">Panelin tamamı (oyun hesabı bağlama, karakterler, destek, bildirimler) sonraki fazlarda bu alana eklenecek.</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-8">
            @csrf
            <x-ui.button type="submit" variant="secondary" icon="log-out">Çıkış yap</x-ui.button>
        </form>
    </section>
</x-layouts.public>
