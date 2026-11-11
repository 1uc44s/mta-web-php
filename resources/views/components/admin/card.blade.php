@props(['title' => null, 'flush' => false])
{{-- ACP kart/panel. flush: içerik kenar boşluksuz (tablolar için). --}}
<section {{ $attributes->class(['rounded-lg border border-border bg-surface-1']) }}>
    @if ($title || isset($actions))
        <header class="flex items-center justify-between gap-3 border-b border-border px-4 py-3">
            @if ($title)<h2 class="text-sm font-semibold text-text">{{ $title }}</h2>@endif
            @if (isset($actions))<div class="flex items-center gap-2">{{ $actions }}</div>@endif
        </header>
    @endif
    <div @class(['p-4' => ! $flush])>{{ $slot }}</div>
</section>
