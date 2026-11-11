@props(['steps' => [], 'direction' => 'auto'])
{{--
    Rota adımları (docs/DESIGN.md §6.2): numaralı duraklar + kesikli turkuaz rota çizgisi.
    Masaüstünde yatay, < 768 px dikey. Çizgi dekoratif; semantik <ol>.
    $steps: [['title' => …, 'text' => …], …]
--}}
<ol {{ $attributes->class([
    'relative grid gap-8',
    'md:auto-cols-fr md:grid-flow-col md:gap-6' => $direction === 'auto',
]) }}>
    @foreach ($steps as $step)
        <li class="relative grid grid-cols-[2.5rem_1fr] gap-x-4 md:grid-cols-1 md:gap-y-4">
            @unless ($loop->last)
                {{-- Rota çizgisi: mobilde dikey, masaüstünde yatay --}}
                <span aria-hidden="true" class="absolute top-10 bottom-[-2rem] left-5 border-l-2 border-dashed border-accent/40 md:top-5 md:right-[-1.5rem] md:bottom-auto md:left-10 md:border-t-2 md:border-l-0"></span>
            @endunless
            <span class="relative z-10 grid size-10 place-items-center rounded-full border-2 font-mono text-sm font-semibold
                {{ $loop->first ? 'border-accent bg-accent text-accent-fg' : 'border-border-input bg-bg text-text' }}">
                {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
            </span>
            <div>
                <p class="font-medium text-text">{{ $step['title'] }}</p>
                @if (! empty($step['text']))
                    <p class="mt-1.5 text-sm text-text-muted">{{ $step['text'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
