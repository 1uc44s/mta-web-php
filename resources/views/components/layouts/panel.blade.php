@props(['title' => null])
{{--
    Oyuncu paneli düzeni — docs/DESIGN.md §7.2: masaüstünde 264 px sidebar (logo, kullanıcı kartı, menü, sunucu durumu),
    56 px üst bar (başlık, bildirim zili, tema, çıkış); mobilde üst bar + alt sekme çubuğu + "Menü" drawer'ı.
    Veri: PanelLayoutComposer.
--}}
<x-layouts.document :title="$title ? $title.' · Panel' : 'Oyuncu paneli'" robots="noindex, nofollow">
    <a href="#icerik" class="sr-only z-50 rounded-md bg-accent px-4 py-2 text-accent-fg focus:not-sr-only focus:fixed focus:top-3 focus:left-3">{{ __('public.skip_to_content') }}</a>

    <div class="min-h-dvh lg:grid lg:grid-cols-[264px_1fr]">
        <aside class="hidden border-r border-border bg-surface-1 lg:sticky lg:top-0 lg:flex lg:h-dvh lg:flex-col">
            @include('components.layouts.partials.panel-nav')
        </aside>

        <div class="flex min-w-0 flex-col pb-20 lg:pb-0">
            <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-border bg-bg/85 px-4 backdrop-blur-md sm:px-6">
                <a href="{{ route('public.home') }}" class="shrink-0 rounded-sm lg:hidden" aria-label="{{ config('app.name') }}"><x-brand.logo /></a>
                <p class="hidden min-w-0 truncate text-sm font-medium text-text lg:block">{{ $title }}</p>
                <div class="ml-auto flex items-center gap-1">
                    <a href="{{ route('panel.notifications.index') }}" class="relative grid size-10 place-items-center rounded-md text-text-muted hover:bg-surface-2 hover:text-text"
                       aria-label="Bildirimler{{ $unreadCount > 0 ? ' ('.$unreadCount.' okunmamış)' : '' }}">
                        <x-lucide-bell class="size-5" aria-hidden="true" />
                        <span data-unread-badge data-count-url="{{ route('panel.notifications.count') }}" @class(['absolute top-1.5 right-1.5 min-w-4 rounded-pill bg-accent px-1 text-center font-mono text-[10px] leading-4 text-accent-fg', 'hidden' => $unreadCount === 0])>{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                    </a>
                    @include('components.layouts.partials.theme-menu')
                    <form method="POST" action="{{ route('logout') }}" data-logout-form>
                        @csrf
                        <button type="submit" class="grid size-10 place-items-center rounded-md text-text-muted hover:bg-surface-2 hover:text-text" aria-label="Çıkış yap">
                            <x-lucide-log-out class="size-5" aria-hidden="true" />
                        </button>
                    </form>
                </div>
            </header>

            <main id="icerik" tabindex="-1" class="flex-1 px-4 py-8 focus:outline-none sm:px-6 lg:px-10">
                @if ($errors->any())
                    <div role="alert" class="mb-6 flex items-start gap-2 rounded-md border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-text">
                        <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0 text-danger" aria-hidden="true" />
                        <ul class="space-y-1">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Mobil alt sekme çubuğu (PWA standalone'da safe-area'ya uyar) --}}
    <nav aria-label="Panel sekmeleri" class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-surface-1/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-md lg:hidden">
        <ul class="grid grid-cols-5">
            @foreach (array_filter($panelMenu, fn ($i) => $i['tab']) as $item)
                <li>
                    <a href="{{ $item['href'] }}" @if ($item['active']) aria-current="page" @endif
                       class="flex min-h-14 flex-col items-center justify-center gap-0.5 text-[11px] text-text-muted aria-[current=page]:text-accent">
                        <x-dynamic-component :component="'lucide-'.$item['icon']" class="size-5" aria-hidden="true" />{{ $item['label'] === 'Genel bakış' ? 'Genel' : $item['label'] }}
                    </a>
                </li>
            @endforeach
            <li>
                <button type="button" data-dialog-open="panel-drawer" aria-controls="panel-drawer" class="flex min-h-14 w-full flex-col items-center justify-center gap-0.5 text-[11px] text-text-muted">
                    <x-lucide-menu class="size-5" aria-hidden="true" />Menü
                </button>
            </li>
        </ul>
    </nav>

    <dialog id="panel-drawer" aria-label="Panel menüsü" class="dialog-mrp m-0 ml-auto h-dvh max-h-dvh w-[min(20rem,100vw)] border-l border-border bg-surface-1 p-0 text-text">
        <form method="dialog" class="absolute top-3 right-3">
            <button class="grid size-10 place-items-center rounded-md text-text-muted hover:bg-surface-2 hover:text-text" aria-label="Menüyü kapat"><x-lucide-x class="size-5" aria-hidden="true" /></button>
        </form>
        <div class="flex h-full flex-col">@include('components.layouts.partials.panel-nav')</div>
    </dialog>

    @if (session('toast'))
        <div data-toast role="status" class="fixed inset-x-4 bottom-20 z-50 mx-auto flex max-w-md items-center gap-3 rounded-md border border-border-strong bg-surface-1 px-4 py-3 text-sm text-text shadow-2 sm:right-4 sm:left-auto sm:mx-0 lg:bottom-4">
            <x-lucide-circle-check class="size-5 shrink-0 text-success" aria-hidden="true" />
            <span>{{ session('toast') }}</span>
        </div>
    @endif
</x-layouts.document>
