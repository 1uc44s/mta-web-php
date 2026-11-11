@props(['title' => null, 'description' => null, 'image' => null, 'type' => 'website', 'jsonLd' => [], 'noindex' => false])
{{-- docs/SRS.md FR-PUB-016: title, description, canonical, Open Graph, Twitter, JSON-LD. --}}
@php
    $settings = app(\App\Services\Settings\SettingsRepository::class);
    $siteName = config('app.name');
    $fullTitle = $title ? $title.' · '.$siteName : $siteName.' — '.$settings->get('site.tagline');
    $description ??= $settings->get('site.description');
    $canonical = url()->current();
    $graph = [
        ['@type' => 'Organization', '@id' => url('/').'#org', 'name' => $siteName, 'url' => url('/')],
        ['@type' => 'WebSite', '@id' => url('/').'#site', 'name' => $siteName, 'url' => url('/'), 'inLanguage' => 'tr-TR', 'publisher' => ['@id' => url('/').'#org']],
        ...$jsonLd,
    ];
@endphp
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">
@if ($noindex)<meta name="robots" content="noindex, nofollow">@endif
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="tr_TR">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
@if ($image)<meta property="og:image" content="{{ $image }}">@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
<link rel="alternate" type="application/rss+xml" title="{{ $siteName }}" href="{{ route('public.feed') }}">
<script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">@json(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)</script>
