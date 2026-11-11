@props(['eyebrow', 'title', 'intro' => null, 'crumbs' => []])
{{-- İç sayfa başlığı: breadcrumb + eyebrow + h1 + giriş; arka planda meridyen çizgileri. --}}
<section class="relative isolate overflow-hidden border-b border-border">
    <x-brand.meridian-globe class="pointer-events-none absolute -top-64 -right-48 -z-10 size-[40rem] text-grid-line lg:-right-24" />
    <div class="mx-auto max-w-[1240px] px-4 pt-10 pb-12 sm:px-8 lg:pt-14 lg:pb-16">
        @if ($crumbs !== [])
            <x-ui.breadcrumb :items="$crumbs" />
        @endif
        <x-brand.eyebrow class="mt-8">{{ $eyebrow }}</x-brand.eyebrow>
        <h1 class="mt-4 max-w-3xl font-display font-expanded text-h1 font-bold tracking-tight text-text">{{ $title }}</h1>
        @if ($intro)
            <p class="mt-5 max-w-2xl text-lg text-text-muted">{{ $intro }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
