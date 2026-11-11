{{-- Paginator varsayılan görünümü (AppServiceProvider). Mobilde yalnızca önceki/sonraki + sayfa bilgisi. --}}
@if ($paginator->hasPages())
    <nav aria-label="Sayfalama" class="mt-12 flex items-center justify-between gap-4 border-t border-border pt-6">
        @if ($paginator->onFirstPage())
            <span class="inline-flex min-h-11 items-center gap-2 px-3 text-sm text-text-subtle" aria-disabled="true">
                <x-lucide-arrow-left class="size-4" aria-hidden="true" /> Önceki
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex min-h-11 items-center gap-2 rounded-md px-3 text-sm text-text-muted hover:bg-surface-2 hover:text-text">
                <x-lucide-arrow-left class="size-4" aria-hidden="true" /> Önceki
            </a>
        @endif

        <p class="font-mono text-xs text-text-subtle sm:hidden">Sayfa {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</p>

        <ul class="hidden items-center gap-1 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="px-2 text-text-subtle" aria-hidden="true">…</li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="grid size-10 place-items-center rounded-md bg-accent-soft font-mono text-sm text-accent">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="grid size-10 place-items-center rounded-md font-mono text-sm text-text-muted hover:bg-surface-2 hover:text-text">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        </ul>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex min-h-11 items-center gap-2 rounded-md px-3 text-sm text-text-muted hover:bg-surface-2 hover:text-text">
                Sonraki <x-lucide-arrow-right class="size-4" aria-hidden="true" />
            </a>
        @else
            <span class="inline-flex min-h-11 items-center gap-2 px-3 text-sm text-text-subtle" aria-disabled="true">
                Sonraki <x-lucide-arrow-right class="size-4" aria-hidden="true" />
            </span>
        @endif
    </nav>
@endif
