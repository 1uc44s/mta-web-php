{{-- CMS §4.2 hero (v2 "Gün Batımı Bulvarı"). Veri: eyebrow, title, title_accent, lead, secondary_label, pillars[]. Birincil CTA her zaman "Oyuna Katıl". --}}
@php
    $pillars = ! empty($pillars) && is_array($pillars) ? array_slice($pillars, 0, 3) : __('home.pillars');
@endphp
<section class="glow-sunset relative isolate overflow-hidden pt-6 lg:pt-2">
    <x-ui.ghost-word class="top-2 -left-6 text-[clamp(6rem,22vw,22rem)] lg:top-0">{{ \App\Support\Brand::shortName() }}</x-ui.ghost-word>

    <div class="mx-auto grid max-w-[1240px] items-center gap-12 px-4 pb-20 sm:px-8 lg:min-h-[min(92svh,900px)] lg:grid-cols-12 lg:pb-24">
        <div class="lg:col-span-7">
            <div data-reveal><x-brand.eyebrow>{{ $eyebrow ?? \App\Support\Brand::shortName().' · Roleplay' }}</x-brand.eyebrow></div>
            <h1 class="mt-7 font-display text-display font-black font-wide tracking-[-0.03em] text-text" data-reveal data-reveal-delay="1">
                {{ $title ?? config('app.name') }}
                @if (! empty($title_accent))
                    <span class="block text-accent">{{ $title_accent }}</span>
                @endif
            </h1>
            @if (! empty($lead))
                <p class="mt-7 max-w-[54ch] text-lg text-text-muted sm:text-xl" data-reveal data-reveal-delay="2">{{ $lead }}</p>
            @endif
            <div class="mt-10 flex flex-wrap items-center gap-3" data-reveal data-reveal-delay="3">
                <x-ui.button size="lg" href="{{ route('public.start') }}#katil" data-dialog-open="join-dialog" icon="play">
                    {{ __('public.join.cta') }}
                </x-ui.button>
                <x-ui.button size="lg" variant="secondary" href="{{ route('public.start') }}" icon-right="arrow-right">
                    {{ $secondary_label ?? __('public.join.how_to_play') }}
                </x-ui.button>
            </div>
            <ul class="mt-12 grid max-w-xl gap-3 sm:grid-cols-3" data-reveal data-reveal-delay="3">
                @foreach ($pillars as $pillar)
                    <li class="glass rounded-[var(--radius-xl)] px-4 py-3.5">
                        <p class="font-display font-black font-wide text-accent">{{ $pillar['title'] ?? '' }}</p>
                        <p class="mt-1 text-sm text-text-muted">{{ $pillar['text'] ?? '' }}</p>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="relative lg:col-span-5">
            <x-brand.skyline class="mx-auto max-w-[34rem] opacity-90 lg:max-w-none" />
            <x-brand.server-card :status="$status" class="relative -mt-24 sm:mx-auto sm:max-w-md lg:absolute lg:right-0 lg:-bottom-10 lg:mt-0 lg:w-[20rem]" />
        </div>
    </div>
</section>