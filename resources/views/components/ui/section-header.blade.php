@props(['number' => null, 'eyebrow' => null, 'title', 'intro' => null, 'level' => 'h2', 'id' => null])
{{-- Bölüm başlığı: koordinat eyebrow + başlık + giriş (docs/DESIGN.md §1, §6.2). --}}
<header {{ $attributes->class(['max-w-2xl']) }}>
    @if ($eyebrow)
        <x-brand.eyebrow :number="$number">{{ $eyebrow }}</x-brand.eyebrow>
    @endif
    <{{ $level }} @if ($id) id="{{ $id }}" @endif class="mt-4 font-display font-wide text-h2 tracking-tight text-text">{{ $title }}</{{ $level }}>
    @if ($intro)
        <p class="mt-4 text-lg text-text-muted">{{ $intro }}</p>
    @endif
    {{ $slot }}
</header>
