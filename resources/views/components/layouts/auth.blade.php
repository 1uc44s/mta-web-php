@props(['title'])
{{-- Kimlik doğrulama yerleşimi: solda marka paneli (lg+), sağda form (docs/DESIGN.md §10 ekran 4). --}}
<x-layouts.document :title="$title" robots="noindex, nofollow">
    <div class="grid min-h-svh lg:grid-cols-[minmax(0,1fr)_minmax(0,34rem)]">
        <aside class="relative isolate hidden overflow-hidden border-r border-border bg-surface-1 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div aria-hidden="true" class="absolute inset-0 -z-20 bg-[radial-gradient(60%_50%_at_30%_70%,var(--c-accent-soft),transparent_70%)]"></div>
            <x-brand.meridian-globe class="pointer-events-none absolute -bottom-72 -left-56 -z-10 size-[52rem] text-grid-line" />
            <a href="{{ route('public.home') }}" class="w-fit rounded-sm"><x-brand.logo /></a>
            <div class="max-w-md">
                <x-brand.eyebrow>{{ __('auth.ui.side.eyebrow') }}</x-brand.eyebrow>
                <p class="mt-5 font-display text-h2 font-bold font-wide text-text">{{ __('auth.ui.side.title') }}</p>
                <ul class="mt-8 space-y-3">
                    @foreach (__('auth.ui.side.points') as $point)
                        <li class="flex gap-3 text-text-muted">
                            <x-lucide-check class="mt-1 size-4 shrink-0 text-accent" aria-hidden="true" />{{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>
            <p class="font-mono text-xs text-text-subtle">{{ __('public.footer.rights', ['year' => now()->year, 'name' => config('app.name')]) }}</p>
        </aside>

        <main class="flex flex-col px-4 py-8 sm:px-10">
            <div class="flex items-center justify-between lg:justify-end">
                <a href="{{ route('public.home') }}" class="rounded-sm lg:hidden"><x-brand.logo /></a>
                <a href="{{ route('public.home') }}" class="inline-flex min-h-10 items-center gap-1.5 rounded-md px-3 text-sm text-text-muted hover:bg-surface-2 hover:text-text">
                    <x-lucide-arrow-left class="size-4" aria-hidden="true" />{{ __('public.home') }}
                </a>
            </div>
            <div class="mx-auto my-auto w-full max-w-sm py-10">
                <h1 class="font-display text-h2 font-bold font-wide text-text">{{ $title }}</h1>
                @isset($intro)<p class="mt-3 text-text-muted">{{ $intro }}</p>@endisset

                @if (session('status'))
                    <p role="status" class="mt-6 flex gap-2 rounded-sm border border-accent/40 bg-accent-soft px-4 py-3 text-sm text-text">
                        <x-lucide-circle-check class="mt-0.5 size-4 shrink-0 text-accent" aria-hidden="true" />{{ session('status') }}
                    </p>
                @endif

                <div class="mt-8">{{ $slot }}</div>
            </div>
        </main>
    </div>
</x-layouts.document>
