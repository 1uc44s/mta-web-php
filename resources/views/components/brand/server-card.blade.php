@props(['status' => null])
{{-- Sunucu durum kartı (US-PUB-03): durum, oyuncu sayısı, tazelik ve adres. Sayılar yalnızca heartbeat'ten gelir. --}}
@php
    use App\Enums\ServerStatus;
    $state = $status?->status;
@endphp
<div {{ $attributes->class(['glass relative rounded-[var(--radius-xl)] shadow-2']) }}>
    <div class="flex items-center justify-between border-b border-border px-5 py-3.5">
        <p class="eyebrow">{{ __('public.status.card_title') }}</p>
        <x-brand.status-pill :status="$status" compact />
    </div>

    @if ($status === null)
        <p class="px-5 py-8 text-sm text-text-muted">{{ __('public.status.no_server') }}</p>
    @else
        <div class="px-5 pt-6 pb-5">
            <p class="font-display text-h3 font-bold font-wide text-text">{{ $status->name }}</p>
            @if ($state->showsPlayers() && $status->players !== null)
                <p class="mt-4 flex items-baseline gap-2 font-mono tabular-nums">
                    <span class="text-5xl font-semibold tracking-tight {{ $state === ServerStatus::Online ? 'text-text' : 'text-text-subtle' }}">{{ $status->players }}</span>
                    @if ($status->maxPlayers)<span class="text-lg text-text-subtle">/ {{ $status->maxPlayers }}</span>@endif
                </p>
            @endif
            <p class="mt-2 text-sm text-text-subtle">
                @switch($state)
                    @case(ServerStatus::Online)
                        {{ __('public.status.live_note', ['time' => $status->lastHeartbeatAt?->locale('tr')->diffForHumans()]) }}
                        @break
                    @case(ServerStatus::Stale)
                        {{ __('public.status.stale_note', ['time' => $status->lastHeartbeatAt?->locale('tr')->diffForHumans()]) }}
                        @break
                    @case(ServerStatus::Maintenance)
                        {{ $status->maintenanceNote ?: __('public.status.maintenance_note') }}
                        @break
                    @default
                        {{ __('public.status.offline_note') }}
                @endswitch
            </p>
        </div>
        <div class="border-t border-border px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="eyebrow">{{ __('public.status.address') }}</p>
                    <code class="mt-1 block font-mono text-base text-text">{{ $status->address() }}</code>
                </div>
                <button type="button" data-copy="{{ $status->address() }}" data-copied-label="{{ __('public.join.copied') }}"
                        class="inline-flex min-h-10 items-center gap-2 rounded-md border border-border-strong px-3 text-sm text-text hover:border-accent hover:text-accent">
                    <x-lucide-copy class="size-4" aria-hidden="true" /><span data-copy-label>{{ __('public.join.copy') }}</span>
                </button>
            </div>
        </div>
    @endif
</div>
