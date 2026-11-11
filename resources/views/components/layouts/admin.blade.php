@props(['title' => null])
{{-- ACP layout — docs/DESIGN.md §7.3: yoğun yerleşim, 248 px sidebar, "YÖNETİM" etiketi + amber üst şerit. Veri: AdminLayoutComposer --}}
<x-layouts.document :title="$title ? $title.' · '.__('admin.area') : __('admin.title')" robots="noindex, nofollow">
    <a href="#icerik" class="sr-only z-50 rounded-md bg-accent px-4 py-2 text-accent-fg focus:not-sr-only focus:fixed focus:top-3 focus:left-3">{{ __('admin.skip') }}</a>

    <div class="min-h-dvh lg:grid lg:grid-cols-[248px_1fr]">
        {{-- Masaüstü sidebar --}}
        <aside class="hidden border-r border-border bg-surface-1 lg:sticky lg:top-0 lg:flex lg:h-dvh lg:flex-col">
            @include('components.layouts.partials.admin-nav')
        </aside>

        <div class="flex min-w-0 flex-col">
            <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-border bg-bg/85 px-4 backdrop-blur-md sm:px-6">
                <button type="button" data-dialog-open="admin-drawer" aria-controls="admin-drawer" class="grid size-10 place-items-center rounded-md text-text hover:bg-surface-2 lg:hidden" aria-label="{{ __('admin.open_menu') }}">
                    <x-lucide-menu class="size-5" aria-hidden="true" />
                </button>
                <span class="rounded-sm bg-amber-soft px-2 py-0.5 font-mono text-[11px] font-semibold tracking-[0.14em] text-amber uppercase lg:hidden">{{ __('admin.area') }}</span>
                <p class="min-w-0 truncate text-sm font-medium text-text">{{ $title }}</p>
                <div class="ml-auto flex items-center gap-1">
                    @include('components.layouts.partials.theme-menu')
                    <a href="{{ route('public.home') }}" class="hidden h-9 items-center gap-2 rounded-md px-3 text-sm text-text-muted hover:bg-surface-2 hover:text-text sm:flex">
                        <x-lucide-external-link class="size-4" aria-hidden="true" />{{ __('admin.back_to_site') }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="grid size-9 place-items-center rounded-md text-text-muted hover:bg-surface-2 hover:text-text" aria-label="{{ __('admin.logout') }}">
                            <x-lucide-log-out class="size-4" aria-hidden="true" />
                        </button>
                    </form>
                </div>
            </header>

            <main id="icerik" tabindex="-1" class="flex-1 px-4 py-6 text-sm focus:outline-none sm:px-6 lg:px-8">
                @if ($errors->any())
                    <div role="alert" class="mb-6 flex items-start gap-2 rounded-md border border-danger/40 bg-danger/10 px-4 py-3 text-text">
                        <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0 text-danger" aria-hidden="true" />
                        <ul class="space-y-1">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Mobil drawer: native dialog --}}
    <dialog id="admin-drawer" aria-label="{{ __('admin.menu') }}" class="dialog-mrp m-0 h-dvh max-h-dvh w-[min(18rem,100vw)] border-r border-border bg-surface-1 p-0 text-text">
        <form method="dialog" class="absolute top-3 right-3">
            <button class="grid size-10 place-items-center rounded-md text-text-muted hover:bg-surface-2 hover:text-text" aria-label="{{ __('admin.close_menu') }}">
                <x-lucide-x class="size-5" aria-hidden="true" />
            </button>
        </form>
        <div class="flex h-full flex-col">@include('components.layouts.partials.admin-nav')</div>
    </dialog>

    @if (session('toast'))
        <div data-toast role="status" class="fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-md items-center gap-3 rounded-md border border-border-strong bg-surface-1 px-4 py-3 text-sm text-text shadow-2 sm:right-4 sm:left-auto sm:mx-0">
            <x-lucide-circle-check class="size-5 shrink-0 text-success" aria-hidden="true" />
            <span>{{ session('toast') }}</span>
        </div>
    @endif
</x-layouts.document>
