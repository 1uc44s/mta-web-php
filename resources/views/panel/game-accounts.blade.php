{{-- Oyun hesaplarım — docs/ROUTES.md §5.3, SRS FR-ACC-001..007. --}}
<x-layouts.panel :title="__('game.accounts_title')">
    <div class="mx-auto max-w-4xl space-y-8">
        <header>
            <x-brand.eyebrow>MTA</x-brand.eyebrow>
            <h1 class="mt-3 font-display text-h2 font-bold font-expanded text-text">{{ __('game.accounts_title') }}</h1>
            <p class="mt-2 max-w-2xl text-text-muted">{{ __('game.accounts_intro') }}</p>
            <p class="mt-2 text-sm text-text-subtle">{{ __('game.linked_count', ['count' => $activeCount, 'max' => $maxLinks]) }}</p>
        </header>

        <section aria-labelledby="h-code" class="space-y-4">
            <h2 id="h-code" class="font-display text-h4 font-semibold text-text">{{ __('game.code.title') }}</h2>

            @if ($freshCode)
                <x-brand.link-code :code="$freshCode" :expires-in="$codeStatus['expires_in']" :status-url="route('panel.game-accounts.link-status')" />
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('panel.game-accounts.link-code.cancel') }}">@csrf<x-ui.button type="submit" size="sm" variant="ghost" icon="x">{{ __('game.code.cancel') }}</x-ui.button></form>
                    <a href="{{ route('panel.game-accounts.index') }}" data-link-code-refresh class="hidden text-sm text-accent underline">{{ __('game.code.regenerate') }}</a>
                </div>
            @else
                <div class="rounded-lg border border-border bg-surface-1 p-6">
                    @if ($codeStatus['state'] === 'pending')
                        <p class="text-sm text-text-muted">{{ __('game.code.hidden') }}</p>
                    @elseif ($codeStatus['state'] === 'linked')
                        <p role="status" class="text-sm text-success">{{ __('game.code.linked', ['name' => $codeStatus['account_name'] ?? '']) }}</p>
                    @elseif ($codeStatus['state'] === 'expired')
                        <p class="text-sm text-text-muted">{{ __('game.code.expired') }}</p>
                    @endif

                    @error('code')<p role="alert" class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror

                    @if ($activeCount < $maxLinks)
                        <form method="POST" action="{{ route('panel.game-accounts.link-code') }}" class="mt-4 flex flex-wrap gap-2">
                            @csrf
                            <x-ui.button type="submit" icon="link">{{ $codeStatus['state'] === 'none' ? __('game.code.generate') : __('game.code.regenerate') }}</x-ui.button>
                        </form>
                        @if ($codeStatus['state'] === 'pending')
                            <form method="POST" action="{{ route('panel.game-accounts.link-code.cancel') }}" class="mt-2">@csrf<x-ui.button type="submit" size="sm" variant="ghost">{{ __('game.code.cancel') }}</x-ui.button></form>
                        @endif
                    @else
                        <p class="mt-2 text-sm text-text-muted">{{ __('game.errors.limit', ['max' => $maxLinks]) }}</p>
                    @endif
                </div>
            @endif
        </section>

        <section aria-labelledby="h-links">
            <h2 id="h-links" class="font-display text-h4 font-semibold text-text">{{ __('game.linked_accounts') }}</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($links as $link)
                    <li class="rounded-lg border border-border bg-surface-1 p-5">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-lucide-gamepad-2 class="size-5 text-accent" aria-hidden="true" />
                            <span class="font-medium text-text">{{ $link->account?->account_name }}</span>
                            <span class="text-xs text-text-subtle">{{ $link->account?->server?->name }}</span>
                            <x-ui.badge :tone="$link->status === 'active' ? 'accent' : 'amber'">{{ __('game.link.status.'.$link->status) }}</x-ui.badge>
                            <span class="ms-auto text-xs text-text-subtle">{{ __('game.link.linked_at') }}: <time datetime="{{ $link->linked_at->toIso8601String() }}">{{ $link->linked_at->format('d.m.Y') }}</time></span>
                        </div>
                        @if ($link->status === 'active')
                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm text-text-muted hover:text-text">{{ __('game.link.unlink') }}</summary>
                                <form method="POST" action="{{ route('panel.game-accounts.unlink-request', $link->id) }}" class="mt-3 space-y-3">
                                    @csrf
                                    <x-ui.field name="reason" :label="__('game.link.unlink_reason')" required><x-ui.input name="reason" multiline rows="3" required minlength="10" maxlength="500" /></x-ui.field>
                                    <x-ui.button type="submit" size="sm" variant="secondary">{{ __('game.link.unlink_submit') }}</x-ui.button>
                                </form>
                            </details>
                        @else
                            <p class="mt-2 text-sm text-amber">{{ __('game.link.unlink_pending') }}</p>
                        @endif
                    </li>
                @empty
                    <li><x-ui.empty-state :title="__('game.no_links')" icon="gamepad-2" /></li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.panel>
