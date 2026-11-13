@props(['number' => null, 'eyebrow' => null, 'title', 'intro' => null, 'level' => 'h2', 'id' => null])
{{-- Bölüm başlığı (v2): çip + ağır başlık + giriş. --}}
<header {{ $attributes->class(['max-w-2xl']) }} data-reveal>
    @if ($eyebrow)
        <x-brand.eyebrow>{{ $eyebrow }}</x-brand.eyebrow>
    @endif
    <{{ $level }} @if ($id) id="{{ $id }}" @endif class="mt-5 font-display font-expanded text-h2 font-black tracking-tight text-text">{{ $title }}</{{ $level }}>
    @if ($intro)
        <p class="mt-4 text-lg text-text-muted">{{ $intro }}</p>
    @endif
    {{ $slot }}
</header>