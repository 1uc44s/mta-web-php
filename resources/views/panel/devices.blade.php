{{-- Cihazlarım — docs/SRS.md FR-ACC-010, ARCHITECTURE §7.6. Ham serial asla gösterilmez. --}}
<x-layouts.panel :title="__('game.device.title')">
    <div class="mx-auto max-w-4xl space-y-8">
        <header>
            <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('game.device.title') }}</h1>
            <p class="mt-2 max-w-2xl text-text-muted">{{ __('game.device.intro') }}</p>
        </header>

        <section class="rounded-lg border border-border bg-surface-1 p-6">
            @if ($freshCode)
                <p class="text-sm text-text-muted">{{ __('game.device.instruction') }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <code class="rounded-sm bg-surface-2 px-3 py-2 font-mono text-h4 text-text">/cihazbagla {{ $freshCode }}</code>
                    <x-ui.button type="button" size="sm" variant="secondary" icon="copy" data-copy="/cihazbagla {{ $freshCode }}"><span data-copy-label>{{ __('game.code.copy') }}</span></x-ui.button>
                </div>
                <p class="mt-3 text-xs text-text-subtle">{{ __('game.code.once') }}</p>
            @elseif (! $hasLink)
                <p class="text-sm text-text-muted">{{ __('game.errors.no_link_for_device') }}</p>
            @else
                @if ($codeStatus['state'] === 'pending')<p class="mb-3 text-sm text-text-muted">{{ __('game.code.hidden') }}</p>@endif
                @error('code')<p role="alert" class="mb-3 text-sm text-danger">{{ $message }}</p>@enderror
                <form method="POST" action="{{ route('panel.devices.link-code') }}">@csrf<x-ui.button type="submit" icon="monitor-smartphone">{{ __('game.device.generate') }}</x-ui.button></form>
            @endif
        </section>

        <ul class="space-y-3">
            @forelse ($devices as $d)
                <li class="flex flex-wrap items-center gap-3 rounded-lg border border-border bg-surface-1 px-5 py-4">
                    <x-lucide-monitor-smartphone class="size-5 text-accent" aria-hidden="true" />
                    <span class="font-medium text-text">{{ $d->label }}</span>
                    <span class="font-mono text-xs text-text-subtle">{{ __('game.device.fingerprint') }}: {{ $d->fingerprint() }}</span>
                    <span class="text-xs text-text-subtle">{{ $d->account?->account_name }}</span>
                    <x-ui.badge :tone="match ($d->status) { 'active' => 'success', 'pending_approval' => 'amber', default => 'neutral' }">{{ __('game.device.status.'.$d->status) }}</x-ui.badge>
                    @if ($d->status !== 'revoked')
                        <form method="POST" action="{{ route('panel.devices.revoke', $d->id) }}" class="ms-auto">@csrf<x-ui.button type="submit" size="sm" variant="ghost">{{ __('game.device.revoke') }}</x-ui.button></form>
                    @endif
                </li>
            @empty
                <li><x-ui.empty-state :title="__('game.device.empty')" icon="monitor-smartphone" /></li>
            @endforelse
        </ul>
    </div>
</x-layouts.panel>
