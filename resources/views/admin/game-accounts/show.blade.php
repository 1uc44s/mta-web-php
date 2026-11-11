{{-- Oyun hesabı detayı — docs/ROUTES.md §6.6. --}}
<x-layouts.admin :title="$account->account_name">
    <x-admin.header :title="$account->account_name" :description="$account->server?->name.' · '.$account->mta_account_ref" :back="route('admin.game-accounts.index')" :back-label="__('game.admin.accounts')" />

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <x-admin.card :title="__('game.admin.links_history')">
            <ul class="divide-y divide-border">
                @forelse ($links as $link)
                    <li class="py-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="font-medium text-text">{{ $link->user?->username ?? '—' }}</span>
                            <x-ui.badge :tone="match ($link->status) { 'active' => 'success', 'unlink_requested' => 'amber', default => 'neutral' }">{{ __('game.link.status.'.$link->status) }}</x-ui.badge>
                            <span class="text-xs text-text-subtle">{{ __('game.link.method.'.$link->verification_method) }}</span>
                            <span class="ms-auto text-xs text-text-subtle">{{ $link->linked_at->format('d.m.Y H:i') }}</span>
                        </div>
                        @if ($link->unlink_reason)
                            <p class="mt-2 text-sm text-text-muted">“{{ $link->unlink_reason }}”</p>
                        @endif
                        @can('game_accounts.manage')
                            @if ($link->isActive())
                                <form method="POST" class="mt-3 flex flex-wrap items-end gap-2">
                                    @csrf
                                    <div class="min-w-64 flex-1">
                                        <label for="reason-{{ $link->id }}" class="sr-only">{{ __('admin.reason') }}</label>
                                        <input id="reason-{{ $link->id }}" name="reason" required minlength="5" placeholder="{{ __('admin.reason') }}"
                                               class="h-9 w-full rounded-md border border-border-input bg-surface-2 px-3 text-sm text-text">
                                    </div>
                                    @if ($link->status === 'unlink_requested')
                                        <x-ui.button type="submit" size="sm" variant="secondary" formaction="{{ route('admin.game-accounts.approve-unlink', $link->id) }}">{{ __('game.admin.approve_unlink') }}</x-ui.button>
                                        <x-ui.button type="submit" size="sm" variant="ghost" formaction="{{ route('admin.game-accounts.reject-unlink', $link->id) }}">{{ __('game.admin.reject_unlink') }}</x-ui.button>
                                    @endif
                                    <x-ui.button type="submit" size="sm" variant="danger" formaction="{{ route('admin.game-accounts.revoke', $link->id) }}">{{ __('game.admin.revoke') }}</x-ui.button>
                                </form>
                            @endif
                        @endcan
                    </li>
                @empty
                    <li class="py-6 text-sm text-text-muted">{{ __('admin.none') }}</li>
                @endforelse
            </ul>
        </x-admin.card>

        <x-admin.card :title="__('game.admin.characters')">
            <ul class="space-y-2 text-sm">
                @forelse ($account->characters as $c)
                    <li><a href="{{ route('admin.characters.show', $c) }}" class="text-text hover:text-accent">{{ $c->name }}</a> <span class="text-text-subtle">· {{ __('game.character.status.'.$c->status) }}</span></li>
                @empty
                    <li class="text-text-muted">{{ __('admin.none') }}</li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>
</x-layouts.admin>
