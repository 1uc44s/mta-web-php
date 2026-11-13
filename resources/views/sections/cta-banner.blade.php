{{-- CMS §4.2 cta_banner (v2): geniş, gün batımı ışımalı son çağrı. --}}
<section class="mx-auto max-w-[1240px] px-4 py-[clamp(4rem,2rem+5vw,8rem)] sm:px-8">
    <div class="glow-cta relative isolate overflow-hidden rounded-[var(--radius-xl)] border border-accent/30 bg-surface-1 px-6 py-16 text-center sm:px-12 sm:py-20" data-reveal>
        <x-ui.ghost-word class="top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-[clamp(5rem,16vw,14rem)]">{{ \App\Support\Brand::shortName() }}</x-ui.ghost-word>
        <x-brand.eyebrow class="mx-auto">{{ $eyebrow ?? 'Hazır mısın?' }}</x-brand.eyebrow>
        <h2 class="mx-auto mt-5 max-w-3xl font-display text-h1 font-black font-expanded tracking-tight text-text">{{ $title }}</h2>
        @if (! empty($text))<p class="mx-auto mt-5 max-w-xl text-lg text-text-muted">{{ $text }}</p>@endif
        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <x-ui.button size="lg" href="{{ route('public.start') }}#katil" data-dialog-open="join-dialog" icon="play">{{ __('public.join.cta') }}</x-ui.button>
            <x-ui.button size="lg" variant="secondary" href="{{ route('public.rules') }}">{{ $secondary_label ?? 'Kuralları oku' }}</x-ui.button>
        </div>
    </div>
</section>