@props(['status' => null, 'compact' => false])
{{--
    Sunucu durum hapı (docs/DESIGN.md §6.1, US-PUB-03). Durum renk + ikon + metinle anlatılır.
    resources/js/modules/server-status.js data-* alanlarını canlı günceller.
--}}
@php
    use App\Enums\ServerStatus;

    $state = $status?->status ?? ServerStatus::Offline;
    $players = $status !== null && $state->showsPlayers() && $status->players !== null
        ? ($status->maxPlayers ? __('public.status.players', ['players' => $status->players, 'max' => $status->maxPlayers]) : __('public.status.players_short', ['players' => $status->players]))
        : null;
@endphp
<span data-status-pill data-status="{{ $state->value }}"
      data-label-online="{{ ServerStatus::Online->label() }}" data-label-stale="{{ ServerStatus::Stale->label() }}"
      data-label-offline="{{ ServerStatus::Offline->label() }}" data-label-maintenance="{{ ServerStatus::Maintenance->label() }}"
      data-players-template="{{ __('public.status.players', ['players' => '{players}', 'max' => '{max}']) }}"
      {{ $attributes->class([
          'group/status inline-flex items-center gap-2 rounded-pill border border-border bg-surface-1 font-medium text-text-muted',
          $compact ? 'px-2.5 py-1 text-xs' : 'px-3 py-1.5 text-sm',
      ]) }}>
    <span class="relative inline-flex size-2 shrink-0 rounded-full
        group-data-[status=online]/status:bg-accent group-data-[status=online]/status:text-accent group-data-[status=online]/status:pulse-live
        group-data-[status=stale]/status:bg-amber
        group-data-[status=maintenance]/status:bg-amber
        group-data-[status=offline]/status:border group-data-[status=offline]/status:border-text-subtle" aria-hidden="true"></span>
    <span data-field="label" class="text-text">{{ $state->label() }}</span>
    <span data-field="players" class="font-mono tabular-nums text-text-subtle {{ $players ? '' : 'hidden' }}">{{ $players }}</span>
</span>
