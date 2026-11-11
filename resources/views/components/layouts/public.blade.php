@props(['title' => null, 'description' => null, 'image' => null, 'type' => 'website', 'jsonLd' => []])
{{-- Public layout (docs/DESIGN.md §7.1). Veri: App\View\Composers\PublicLayoutComposer --}}
@php
    $discord = $site['social.discord_invite'] ?? null;
    $navItems = $navigation['header'];
@endphp
<x-layouts.document>
    <x-slot:head>
        <x-seo :title="$title" :description="$description" :image="$image" :type="$type" :json-ld="$jsonLd" />
    </x-slot:head>

    <a href="#icerik" class="sr-only z-50 rounded-md bg-accent px-4 py-2 text-accent-fg focus:not-sr-only focus:fixed focus:top-3 focus:left-3">
        {{ __('public.skip_to_content') }}
    </a>

    @if ($site['maintenance.banner_enabled'] ?? false)
        <div class="border-b border-amber/30 bg-amber-soft">
            <p class="mx-auto flex max-w-[1240px] items-center gap-2 px-4 py-2.5 text-sm text-text sm:px-8">
                <x-lucide-wrench class="size-4 shrink-0 text-amber" aria-hidden="true" />
                <span class="sr-only">{{ __('public.maintenance_banner') }}:</span>
                {{ $site['maintenance.banner_text'] }}
            </p>
        </div>
    @endif

    <header data-site-header class="sticky top-0 z-40 border-b border-transparent bg-bg/80 backdrop-blur-md transition-colors duration-[var(--dur-base)] data-[scrolled=true]:border-border">
        <div class="mx-auto flex h-16 max-w-[1240px] items-center gap-6 px-4 sm:px-8">
            <a href="{{ route('public.home') }}" class="shrink-0 rounded-sm" aria-label="{{ config('app.name') }} — {{ __('public.home') }}">
                <x-brand.logo />
            </a>

            <nav aria-label="{{ __('public.main_navigation') }}" class="hidden flex-1 lg:block">
                <ul class="flex items-center gap-1">
                    @foreach ($navItems as $item)
                        <li>
                            <a href="{{ $item['href'] }}" @if ($item['new_tab']) target="_blank" rel="noopener" @endif
                               @if ($item['active']) aria-current="page" @endif
                               class="relative rounded-md px-3 py-2 text-sm text-text-muted transition-colors hover:text-text aria-[current=page]:text-text aria-[current=page]:after:absolute aria-[current=page]:after:inset-x-3 aria-[current=page]:after:-bottom-[13px] aria-[current=page]:after:h-0.5 aria-[current=page]:after:bg-accent">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                {{-- Responsive görünürlük sarmalayıcıda: bileşenin kendi display sınıfıyla çakışmasın. --}}
                <div class="hidden sm:block"><x-brand.status-pill :status="$serverStatus" compact /></div>

                @include('components.layouts.partials.theme-menu')

                @if ($discord)
                    <a href="{{ \App\Support\Url::safe($discord) }}" target="_blank" rel="noopener" class="hidden size-10 place-items-center rounded-md text-text-muted hover:bg-surface-2 hover:text-text md:grid" aria-label="Discord">
                        <x-lucide-message-circle class="size-5" aria-hidden="true" />
                    </a>
                @endif

                @auth
                    <x-ui.button size="sm" variant="ghost" :href="route('panel.dashboard')" icon="layout-dashboard" class="max-sm:px-2">
                        <span class="max-sm:sr-only">Panel</span>
                    </x-ui.button>
                @else
                    <x-ui.button size="sm" variant="ghost" :href="route('login')" icon="log-in" class="max-sm:px-2">
                        <span class="max-sm:sr-only">Giriş</span>
                    </x-ui.button>
                @endauth

                <div class="hidden sm:block">
                    <x-ui.button size="sm" href="{{ route('public.start') }}#katil" data-dialog-open="join-dialog">
                        {{ __('public.join.cta') }}
                    </x-ui.button>
                </div>

                <button type="button" data-dialog-open="nav-drawer" class="grid size-10 place-items-center rounded-md text-text hover:bg-surface-2 lg:hidden" aria-label="{{ __('public.open_menu') }}" aria-controls="nav-drawer">
                    <x-lucide-menu class="size-5" aria-hidden="true" />
                </button>
            </div>
        </div>
    </header>

    {{-- Mobil menü: native dialog (focus trap + Esc) --}}
    <dialog id="nav-drawer" aria-label="{{ __('public.menu') }}" class="dialog-mrp m-0 ml-auto h-dvh max-h-dvh w-[min(22rem,100vw)] border-l border-border bg-surface-1 p-0 text-text">
        <div class="flex h-16 items-center justify-between border-b border-border px-5">
            <x-brand.logo />
            <form method="dialog">
                <button class="grid size-10 place-items-center rounded-md text-text-muted hover:bg-surface-2 hover:text-text" aria-label="{{ __('public.close_menu') }}">
                    <x-lucide-x class="size-5" aria-hidden="true" />
                </button>
            </form>
        </div>
        <nav aria-label="{{ __('public.main_navigation') }}" class="px-3 py-4">
            <ul class="space-y-1">
                @foreach ($navItems as $item)
                    <li>
                        <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif
                           class="flex min-h-12 items-center justify-between rounded-md px-3 text-base text-text-muted hover:bg-surface-2 hover:text-text aria-[current=page]:bg-accent-soft aria-[current=page]:text-accent">
                            {{ $item['label'] }}
                            <x-lucide-chevron-right class="size-4 opacity-50" aria-hidden="true" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
        <div class="space-y-3 border-t border-border px-5 py-5">
            <x-brand.status-pill :status="$serverStatus" />
            <x-ui.button href="{{ route('public.start') }}#katil" data-dialog-open="join-dialog" class="w-full">{{ __('public.join.cta') }}</x-ui.button>
        </div>
    </dialog>

    <main id="icerik" tabindex="-1" class="focus:outline-none">
        {{ $slot }}
    </main>

    @include('components.layouts.partials.footer')

    <x-brand.join-dialog :status="$serverStatus" />

    @if (! request()->cookie('mrp_consent'))
        @include('components.layouts.partials.cookie-banner')
    @endif

    @if (session('toast'))
        <div data-toast role="status" class="fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-md items-center gap-3 rounded-md border border-border-strong bg-surface-1 px-4 py-3 text-sm text-text shadow-2 sm:right-4 sm:left-auto sm:mx-0">
            <x-lucide-circle-check class="size-5 shrink-0 text-success" aria-hidden="true" />
            <span>{{ session('toast') }}</span>
        </div>
    @endif
</x-layouts.document>
