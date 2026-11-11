{{-- CMS §4.2 hero. Veri: eyebrow, title, title_accent, lead, secondary_label. Birincil CTA her zaman "Oyuna Katıl". --}}
<section class="relative isolate overflow-hidden border-b border-border">
    <div aria-hidden="true" class="absolute inset-0 -z-20 bg-[radial-gradient(55%_60%_at_78%_40%,var(--c-accent-soft),transparent_70%)]"></div>
    <x-brand.meridian-globe class="pointer-events-none absolute top-1/2 right-[-22rem] -z-10 size-[58rem] -translate-y-1/2 text-grid-line lg:right-[-12rem] lg:size-[70rem]" />

    {{-- Rota çizgisi: soldan durum kartına uzanan kesikli turkuaz yol (dekoratif) --}}
    <svg aria-hidden="true" focusable="false" viewBox="0 0 1440 820" preserveAspectRatio="none" class="pointer-events-none absolute inset-0 -z-10 hidden h-full w-full lg:block">
        <path d="M-40 760 C 260 700, 420 790, 640 640 S 960 330, 1120 360" fill="none" class="stroke-accent/40" stroke-width="1.5" stroke-dasharray="5 9" vector-effect="non-scaling-stroke" />
        <circle cx="640" cy="640" r="4" class="fill-accent/60" />
        <circle cx="1120" cy="360" r="5" class="fill-accent" />
    </svg>

    <div class="mx-auto grid max-w-[1240px] items-center gap-12 px-4 pt-16 pb-20 sm:px-8 lg:min-h-[min(82svh,820px)] lg:grid-cols-12 lg:py-24">
        <div class="lg:col-span-7">
            <x-brand.eyebrow :number="$number">{{ $eyebrow ?? 'San Andreas · Roleplay' }}</x-brand.eyebrow>
            <h1 class="mt-6 font-display text-display font-extrabold font-expanded tracking-tight text-text">
                {{ $title ?? config('app.name') }}
                @if (! empty($title_accent))
                    <span class="block text-accent">{{ $title_accent }}</span>
                @endif
            </h1>
            @if (! empty($lead))
                <p class="mt-6 max-w-[52ch] text-lg text-text-muted sm:text-xl">{{ $lead }}</p>
            @endif
            <div class="mt-10 flex flex-wrap items-center gap-3">
                <x-ui.button size="lg" href="{{ route('public.start') }}#katil" data-dialog-open="join-dialog" icon="gamepad-2">
                    {{ __('public.join.cta') }}
                </x-ui.button>
                <x-ui.button size="lg" variant="secondary" href="{{ route('public.start') }}" icon-right="arrow-right">
                    {{ $secondary_label ?? __('public.join.how_to_play') }}
                </x-ui.button>
            </div>
        </div>
        <div class="lg:col-span-5">
            <x-brand.server-card :status="$status" class="lg:ml-auto lg:max-w-md" />
        </div>
    </div>
</section>
