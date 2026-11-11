@props(['code', 'expiresIn' => 600, 'statusUrl' => null])
{{-- docs/DESIGN.md §6.2 x-brand.link-code: 8 hücre, iki grup, dairesel geri sayım, oyun içi komut + kopyala. --}}
@php
    $chars = str_split($code);
    $command = '/webbagla '.$code;
@endphp
<div data-link-code data-expires-in="{{ (int) $expiresIn }}" @if ($statusUrl) data-status-url="{{ $statusUrl }}" @endif
     {{ $attributes->class(['rounded-lg border border-border-strong bg-surface-1 p-6']) }}>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-1.5 font-mono text-h2 font-bold text-text transition-opacity" aria-label="{{ __('game.code.title') }}: {{ implode(' ', $chars) }}" data-link-code-cells>
            @foreach ($chars as $i => $char)
                @if ($i === 4)<span class="px-1 text-text-subtle" aria-hidden="true">–</span>@endif
                <span class="grid size-11 place-items-center rounded-sm border border-border-input bg-surface-2" aria-hidden="true">{{ $char }}</span>
            @endforeach
        </div>
        <div class="flex items-center gap-3 text-sm text-text-muted">
            <svg viewBox="0 0 36 36" class="size-10 -rotate-90" aria-hidden="true">
                <circle cx="18" cy="18" r="15.5" fill="none" stroke="currentColor" stroke-opacity=".2" stroke-width="3" />
                <circle data-link-code-ring cx="18" cy="18" r="15.5" fill="none" stroke="var(--color-accent)" stroke-width="3" stroke-dasharray="97.4" stroke-dashoffset="0" stroke-linecap="round" />
            </svg>
            <span>{{ __('game.code.expires') }}: <time data-link-code-timer class="font-mono text-text">{{ gmdate('i:s', max(0, (int) $expiresIn)) }}</time></span>
        </div>
    </div>
    <p class="mt-5 text-sm text-text-muted">{{ __('game.code.instruction') }}</p>
    <div class="mt-2 flex flex-wrap items-center gap-3">
        <code class="rounded-sm bg-surface-2 px-3 py-2 font-mono text-text">{{ $command }}</code>
        <x-ui.button type="button" size="sm" variant="secondary" icon="copy" data-copy="{{ $command }}"><span data-copy-label>{{ __('game.code.copy') }}</span></x-ui.button>
    </div>
    <p class="mt-4 text-xs text-text-subtle">{{ __('game.code.once') }}</p>
    <p data-link-code-state data-msg-expired="{{ __('game.code.expired') }}" data-msg-linked="{{ __('game.code.linked', ['name' => ':name']) }}"
       role="status" aria-live="polite" class="mt-3 text-sm text-text-muted">{{ __('game.code.pending') }}</p>
</div>
