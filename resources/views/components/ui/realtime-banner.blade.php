@props(['channels', 'event', 'message'])
{{-- Gerçek zaman "yeni hareket — yenile" bandı (Faz 10, ARCHITECTURE §9). Reverb kapalıyken hiç basılmaz; echo.js açar. --}}
@if (\App\Support\Realtime::enabled())
    <div hidden role="status" data-realtime-banner="{{ $channels }}" data-realtime-event="{{ $event }}"
         {{ $attributes->class(['mb-4 flex flex-wrap items-center gap-3 rounded-md border border-accent/40 bg-surface-2 px-4 py-2.5 text-sm text-text']) }}>
        <x-lucide-radio class="size-4 text-accent" aria-hidden="true" />
        <span>{{ $message }}</span>
        <a href="{{ request()->fullUrl() }}" class="font-medium text-accent hover:underline">{{ __('notifications.realtime.reload') }}</a>
    </div>
@endif
