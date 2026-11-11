{{-- Filtre çipleri (GET parametresi → paylaşılabilir URL). $options: [value => label], $active, $route, $param --}}
<nav aria-label="Filtre" class="flex flex-wrap gap-2">
    <a href="{{ route($route) }}" @if ($active === null) aria-current="page" @endif
       class="inline-flex min-h-9 items-center rounded-pill border border-border px-4 text-sm text-text-muted hover:border-border-strong hover:text-text aria-[current=page]:border-accent aria-[current=page]:bg-accent-soft aria-[current=page]:text-accent">
        {{ __('public.all') }}
    </a>
    @foreach ($options as $value => $label)
        <a href="{{ route($route, [$param => $value]) }}" @if ($active === $value) aria-current="page" @endif
           class="inline-flex min-h-9 items-center rounded-pill border border-border px-4 text-sm text-text-muted hover:border-border-strong hover:text-text aria-[current=page]:border-accent aria-[current=page]:bg-accent-soft aria-[current=page]:text-accent">
            {{ $label }}
        </a>
    @endforeach
</nav>
