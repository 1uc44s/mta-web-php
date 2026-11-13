{{-- CMS §4.2 stats_strip (v2): yalnızca hesaplanan, doğrulanabilir sayılar (sahte sayı yok). --}}
<section aria-labelledby="stats-title" class="mx-auto max-w-[1240px] px-4 py-10 sm:px-8">
    <h2 id="stats-title" class="sr-only">{{ $title ?? 'Sayılarla' }}</h2>
    <dl class="grid gap-4 sm:grid-cols-3">
        @foreach ($metrics as $metric)
            <div class="glass rounded-[var(--radius-xl)] px-7 py-8" data-reveal data-reveal-delay="{{ $loop->index + 1 }}">
                <dt class="eyebrow text-text-muted">{{ __('public.home_sections.stats.'.$metric['key']) }}</dt>
                <dd class="mt-3 font-display text-5xl font-black font-wide tracking-tight text-text tabular-nums">{{ number_format($metric['value'], 0, ',', '.') }}</dd>
            </div>
        @endforeach
    </dl>
</section>