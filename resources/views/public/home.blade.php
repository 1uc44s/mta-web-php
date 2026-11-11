{{-- Ana sayfa: CMS bölümleri sırayla (docs/SRS.md FR-PUB-003). Sayfa yayında değilse dürüst bir bekleme ekranı. --}}
<x-layouts.public :description="$page?->meta_description">
    @forelse ($sections as $section)
        @include($section['view'], $section['data'] + ['number' => $loop->iteration])
    @empty
        <section class="relative isolate overflow-hidden">
            <x-brand.meridian-globe class="pointer-events-none absolute top-1/2 left-1/2 -z-10 size-[52rem] -translate-x-1/4 -translate-y-1/2 text-grid-line lg:size-[64rem] lg:translate-x-0" />
            <div class="mx-auto max-w-[1240px] px-4 py-28 sm:px-8">
                <x-brand.eyebrow number="1">San Andreas · Roleplay</x-brand.eyebrow>
                <h1 class="mt-6 max-w-[14ch] font-display text-display font-extrabold font-expanded tracking-tight text-text">{{ config('app.name') }}</h1>
                <p class="mt-6 max-w-[48ch] text-lg text-text-muted">{{ __('home.placeholder.lead') }}</p>
            </div>
        </section>
    @endforelse
</x-layouts.public>
