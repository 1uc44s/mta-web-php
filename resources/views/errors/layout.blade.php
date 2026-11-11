{{-- Hata sayfaları kabuğu. Oturum veya kimlik doğrulamaya bağımlı değildir: session'dan önce oluşan hatalarda da çalışır. --}}
@php($requestId = \App\Support\RequestId::current())
<x-layouts.document :title="__('errors.pages.'.$code.'.title')" robots="noindex, nofollow">
    <main class="relative isolate flex min-h-svh items-center overflow-hidden">
        <x-brand.meridian-globe class="pointer-events-none absolute top-1/2 -right-64 -z-10 size-[44rem] -translate-y-1/2 text-grid-line sm:-right-40 lg:-right-16 lg:size-[56rem]" />

        <div class="mx-auto w-full max-w-[1240px] px-4 py-20 sm:px-8">
            <div class="max-w-xl">
                <p class="eyebrow">
                    <span class="text-accent">§</span> {{ config('app.name') }} · Hata {{ $code }}
                </p>

                <p class="mt-6 font-mono text-[clamp(4.5rem,3rem+8vw,9rem)] leading-none font-semibold tracking-tight text-text-subtle tabular-nums" aria-hidden="true">
                    {{ $code }}
                </p>

                <h1 class="mt-6 font-display text-h1 font-expanded text-text">
                    {{ __('errors.pages.'.$code.'.title') }}
                </h1>

                <p class="mt-4 max-w-[52ch] text-lg text-text-muted">
                    {{ __('errors.pages.'.$code.'.text') }}
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-3">
                    <a href="{{ url('/') }}"
                       class="inline-flex min-h-11 items-center rounded-md bg-accent px-5 font-medium text-accent-fg transition-colors duration-[var(--dur-fast)] hover:bg-accent-hover">
                        {{ __('errors.pages.home') }}
                    </a>
                </div>

                @if ($requestId)
                    <p class="mt-12 border-t border-border pt-4 text-sm text-text-subtle">
                        {{ __('errors.pages.request_id') }}:
                        <span class="font-mono text-text-muted select-all">{{ $requestId }}</span>
                    </p>
                @endif
            </div>
        </div>
    </main>
</x-layouts.document>
