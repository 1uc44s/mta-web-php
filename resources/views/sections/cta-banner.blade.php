{{-- CMS §4.2 cta_banner: son çağrı. --}}
<section class="mx-auto max-w-[1240px] px-4 py-[clamp(3rem,2rem+4vw,6rem)] sm:px-8">
    <div class="relative isolate overflow-hidden rounded-lg border border-accent/30 bg-surface-1 px-6 py-14 text-center sm:px-12">
        <div aria-hidden="true" class="absolute inset-0 -z-20 bg-[radial-gradient(50%_80%_at_50%_100%,var(--c-accent-soft),transparent_70%)]"></div>
        <x-brand.meridian-globe class="pointer-events-none absolute top-full left-1/2 -z-10 size-[44rem] -translate-x-1/2 -translate-y-1/3 text-grid-line" />
        <x-brand.eyebrow :number="$number" class="justify-center">{{ $eyebrow ?? 'Hazır mısın?' }}</x-brand.eyebrow>
        <h2 class="mx-auto mt-4 max-w-2xl font-display text-h2 font-bold font-wide text-text">{{ $title }}</h2>
        @if (! empty($text))<p class="mx-auto mt-4 max-w-xl text-lg text-text-muted">{{ $text }}</p>@endif
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <x-ui.button size="lg" href="{{ route('public.start') }}#katil" data-dialog-open="join-dialog" icon="gamepad-2">{{ __('public.join.cta') }}</x-ui.button>
            <x-ui.button size="lg" variant="secondary" href="{{ route('public.rules') }}">{{ $secondary_label ?? 'Kuralları oku' }}</x-ui.button>
        </div>
    </div>
</section>
