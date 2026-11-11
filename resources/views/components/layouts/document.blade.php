@props([
    'title' => null,
    'description' => null,
    'robots' => null,
    'theme' => 'dark',
    'effects' => 'full',
])
{{-- Tüm sayfaların ortak HTML kabuğu. Tema/efekt değerleri sunucuda render edilir: flaş yok (docs/DESIGN.md §4). --}}
<!DOCTYPE html>
<html lang="tr" data-theme="{{ $theme }}" data-effects="{{ $effects }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark light">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($robots)
        <meta name="robots" content="{{ $robots }}">
    @endif
    <title>{{ $title ? $title.' · '.config('app.name') : config('app.name') }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body {{ $attributes->class(['min-h-svh bg-bg text-text antialiased']) }}>
    {{ $slot }}
</body>
</html>
