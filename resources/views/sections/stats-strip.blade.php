{{-- CMS §4.2 stats_strip: yalnızca hesaplanan, doğrulanabilir sayılar (sahte sayı yok). --}}
<section aria-label="{{ $title ?? 'Sayılarla' }}" class="border-y border-border">
    <dl class="mx-auto grid max-w-[1240px] divide-border px-4 sm:grid-cols-3 sm:divide-x sm:px-8">
        @foreach ($metrics as $metric)
            <div class="py-8 sm:px-8 sm:first:pl-0">
                <dt class="eyebrow">{{ __('public.home_sections.stats.'.$metric['key']) }}</dt>
                <dd class="mt-2 font-mono text-4xl font-semibold tracking-tight text-text tabular-nums">{{ number_format($metric['value'], 0, ',', '.') }}</dd>
            </div>
        @endforeach
    </dl>
</section>
