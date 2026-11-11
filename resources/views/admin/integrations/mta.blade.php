{{-- MTA entegrasyonu — docs/ROUTES.md §6.11, SRS FR-BRG-004. Secret yalnızca oluşturma yanıtında görünür. --}}
<x-layouts.admin :title="__('game.admin.integrations')">
    <x-admin.header :title="__('game.admin.integrations')" />

    @if ($issued)
        <div role="alert" class="mb-6 rounded-lg border border-amber/50 bg-amber/10 p-5">
            <p class="text-sm font-medium text-text">{{ __('game.admin.secret_once') }}</p>
            <dl class="mt-3 space-y-2 font-mono text-sm">
                <div class="flex flex-wrap items-center gap-2"><dt class="text-text-subtle">key_id</dt><dd class="text-text">{{ $issued['key_id'] }}</dd></div>
                <div class="flex flex-wrap items-center gap-2"><dt class="text-text-subtle">secret</dt><dd class="break-all text-text">{{ $issued['secret'] }}</dd>
                    <x-ui.button type="button" size="sm" variant="secondary" icon="copy" data-copy="{{ $issued['secret'] }}"><span data-copy-label>{{ __('game.code.copy') }}</span></x-ui.button></div>
            </dl>
        </div>
    @endif

    <section class="space-y-4">
        <h2 class="font-display text-h4 font-semibold text-text">{{ __('game.admin.servers') }}</h2>
        @foreach ($servers as $server)
            @php $snap = $statuses[$server->id]; @endphp
            <x-admin.card>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="font-medium text-text">{{ $server->name }}</span>
                    <span class="font-mono text-xs text-text-subtle">{{ $server->slug }} · {{ $server->connectAddress() }}</span>
                    <x-ui.badge :tone="match ($snap->status->value) { 'online' => 'success', 'stale', 'maintenance' => 'amber', default => 'neutral' }">{{ $snap->status->value }}</x-ui.badge>
                    @if ($server->is_primary)<x-ui.badge tone="accent">{{ __('game.admin.primary') }}</x-ui.badge>@endif
                    <span class="ms-auto text-xs text-text-subtle">{{ __('game.admin.last_heartbeat') }}: {{ $server->last_heartbeat_at?->diffForHumans() ?? __('game.admin.never') }}</span>
                </div>
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm text-text-muted hover:text-text">{{ __('game.admin.save_server') }}</summary>
                    @include('admin.integrations.server-form', ['server' => $server, 'action' => route('admin.integrations.mta.servers.update', $server->id)])
                </details>
                <details class="mt-2">
                    <summary class="cursor-pointer text-sm text-text-muted hover:text-text">{{ __('game.admin.new_key') }}</summary>
                    <form method="POST" action="{{ route('admin.integrations.mta.credentials.store', $server->id) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <fieldset class="sm:col-span-2">
                            <legend class="text-sm font-medium text-text">{{ __('game.admin.scopes') }}</legend>
                            <div class="mt-2 flex flex-wrap gap-4">
                                @foreach ($scopes as $scope)
                                    <label class="flex items-center gap-2 text-sm text-text"><input type="checkbox" name="scopes[]" value="{{ $scope }}" checked> <span class="font-mono">{{ $scope }}</span></label>
                                @endforeach
                            </div>
                        </fieldset>
                        <x-ui.field name="expires_at" :label="__('game.admin.expires_at')"><x-ui.input name="expires_at" type="datetime-local" /></x-ui.field>
                        <x-ui.field name="reason" :label="__('admin.reason')" required><x-ui.input name="reason" required /></x-ui.field>
                        <div class="sm:col-span-2"><x-ui.button type="submit" size="sm" icon="key-round">{{ __('game.admin.new_key') }}</x-ui.button></div>
                    </form>
                </details>
            </x-admin.card>
        @endforeach

        <details class="rounded-lg border border-dashed border-border p-5">
            <summary class="cursor-pointer text-sm font-medium text-text">{{ __('game.admin.add_server') }}</summary>
            @include('admin.integrations.server-form', ['server' => null, 'action' => route('admin.integrations.mta.servers.store')])
        </details>
    </section>

    <section class="mt-10">
        <h2 class="font-display text-h4 font-semibold text-text">{{ __('game.admin.credentials') }}</h2>
        <x-admin.card flush class="mt-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border text-xs text-text-subtle">
                        <tr>
                            <th scope="col" class="px-4 py-2.5 font-medium">key_id</th>
                            <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.admin.server') }}</th>
                            <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.admin.scopes') }}</th>
                            <th scope="col" class="px-4 py-2.5 font-medium">{{ __('game.admin.last_used') }}</th>
                            <th scope="col" class="px-4 py-2.5 font-medium"><span class="sr-only">{{ __('game.admin.revoke_key') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($credentials as $cred)
                            <tr class="h-11">
                                <td class="px-4 font-mono text-xs text-text">{{ $cred->key_id }}</td>
                                <td class="px-4 text-text-muted">{{ $cred->server?->name }}</td>
                                <td class="px-4 font-mono text-xs text-text-muted">{{ implode(', ', $cred->scopes) }}</td>
                                <td class="px-4 text-text-muted">{{ $cred->last_used_at?->diffForHumans() ?? __('game.admin.never') }}</td>
                                <td class="px-4">
                                    @if ($cred->revoked_at)
                                        <x-ui.badge tone="neutral">{{ __('game.admin.key_revoked') }}</x-ui.badge>
                                    @elseif (! $cred->isUsable())
                                        <x-ui.badge tone="neutral">{{ __('game.admin.key_expired') }}</x-ui.badge>
                                    @else
                                        <form method="POST" action="{{ route('admin.integrations.mta.credentials.revoke', $cred->id) }}" class="flex items-center gap-2">
                                            @csrf
                                            <label for="rv-{{ $cred->id }}" class="sr-only">{{ __('admin.reason') }}</label>
                                            <input id="rv-{{ $cred->id }}" name="reason" required minlength="5" placeholder="{{ __('admin.reason') }}" class="h-8 w-40 rounded-md border border-border-input bg-surface-2 px-2 text-xs text-text">
                                            <x-ui.button type="submit" size="sm" variant="danger">{{ __('game.admin.revoke_key') }}</x-ui.button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('admin.none') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </section>
</x-layouts.admin>
