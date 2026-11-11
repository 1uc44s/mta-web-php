{{-- Faz 1 geçici ana sayfası. Faz 2'de CMS bölümlerinden üretilen ana sayfa ile değiştirilir (docs/ROADMAP.md Faz 2.6). --}}
<x-layouts.document :description="__('home.placeholder.description')">
    <main class="relative isolate flex min-h-svh items-center overflow-hidden">
        <x-brand.meridian-globe class="pointer-events-none absolute top-1/2 left-1/2 -z-10 size-[52rem] -translate-x-1/4 -translate-y-1/2 text-grid-line lg:size-[64rem] lg:translate-x-0" />

        <div class="mx-auto w-full max-w-[1240px] px-4 py-20 sm:px-8">
            <p class="eyebrow">
                <span class="text-accent">§01</span> · San Andreas · Roleplay
            </p>

            <h1 class="mt-6 max-w-[14ch] font-display text-display font-extrabold font-expanded tracking-tight text-text">
                {{ config('app.name') }}
            </h1>

            <p class="mt-6 max-w-[48ch] text-lg text-text-muted">
                {{ __('home.placeholder.lead') }}
            </p>

            <p class="mt-10 inline-flex items-center gap-3 rounded-pill border border-border-strong bg-surface-1 px-4 py-2 text-sm text-text-muted">
                <span class="size-2 rounded-full bg-amber" aria-hidden="true"></span>
                {{ __('home.placeholder.status') }}
            </p>
        </div>
    </main>
</x-layouts.document>
