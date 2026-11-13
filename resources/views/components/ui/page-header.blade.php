@props(['eyebrow', 'title', 'intro' => null, 'crumbs' => []])
{{-- İç sayfa başlığı (v2): breadcrumb + çip + h1 + giriş; arkada sayfa adının hayalet kelimesi. --}}
<section class="relative isolate overflow-hidden pt-6 lg:pt-10">
    <x-ui.ghost-word class="top-0 -left-4 text-[clamp(5rem,16vw,15rem)] lg:top-2">{{ \Illuminate\Support\Str::of($eyebrow)->before(' ') }}</x-ui.ghost-word>
    <div class="mx-auto max-w-[1240px] px-4 pb-12 sm:px-8 lg:pb-16">
        @if ($crumbs !== [])
            <x-ui.breadcrumb :items="$crumbs" />
        @endif
        <x-brand.eyebrow class="mt-8">{{ $eyebrow }}</x-brand.eyebrow>
        <h1 class="mt-5 max-w-4xl font-display font-expanded text-h1 font-black tracking-tight text-text">{{ $title }}</h1>
        @if ($intro)
            <p class="mt-5 max-w-2xl text-lg text-text-muted">{{ $intro }}</p>
        @endif
        {{ $slot }}
    </div>
</section>