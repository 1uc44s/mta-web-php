@props([
    'title' => null,
    'description' => null,
    'robots' => null,
])
{{--
    Tüm sayfaların ortak HTML kabuğu (docs/DESIGN.md §4).
    Tema/efekt sunucuda render edilir; yalnızca "system" seçiliyse nonce'lu tek satır script boyamadan önce çözer.
--}}
@php
    $theme = \App\Support\ThemePreference::theme();
    $effects = \App\Support\ThemePreference::effects();
@endphp
<!DOCTYPE html>
<html lang="tr" data-theme="{{ $theme === 'system' ? 'dark' : $theme }}" data-theme-pref="{{ $theme }}" data-effects="{{ $effects }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark light">
    <meta name="theme-color" content="#0b0d10">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($theme === 'system')
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">document.documentElement.dataset.theme=matchMedia('(prefers-color-scheme: light)').matches?'light':'dark'</script>
    @endif
    @if (isset($head))
        {{ $head }}
    @else
        <title>{{ $title ? $title.' · '.config('app.name') : config('app.name') }}</title>
        @if ($description)<meta name="description" content="{{ $description }}">@endif
        @if ($robots)<meta name="robots" content="{{ $robots }}">@endif
    @endif
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body {{ $attributes->class(['min-h-svh bg-bg text-text antialiased']) }}>
    {{ $slot }}
    <div id="sr-live" class="sr-only" aria-live="polite" aria-atomic="true"></div>
</body>
</html>
