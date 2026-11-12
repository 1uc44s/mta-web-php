{{-- Oyun komut kuyruğu — docs/ROUTES.md §6.7, ARCHITECTURE §7.5. --}}
<x-layouts.admin :title="__('moderation.admin.commands')">
    <x-admin.header :title="__('moderation.admin.commands')" />

    <x-admin.card :title="__('moderation.admin.send_command')" class="mb-6">
        <form method="POST" action="{{ route('admin.game-commands.store') }}" class="grid gap-3 sm:grid-cols-3">
            @csrf
            <x-ui.field name="type" :label="__('moderation.admin.command_type')" required>
                <select id="f-type" name="type" class="h-10 rounded-sm border border-border-input bg-surface-2 px-3 text-sm text-text">
                    @foreach ($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                </select>
            </x-ui.field>
            <x-ui.field name="mta_server_id" :label="__('moderation.admin.server')" required>
                <select id="f-mta_server_id" name="mta_server_id" class="h-10 rounded-sm border border-border-input bg-surface-2 px-3 text-sm text-text">
                    @foreach ($servers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
            </x-ui.field>
            <x-ui.field name="account" :label="__('moderation.admin.account')"><x-ui.input name="account" /></x-ui.field>
            <x-ui.field name="message" :label="__('moderation.admin.message')" class="sm:col-span-2"><x-ui.input name="message" maxlength="200" /></x-ui.field>
            <x-ui.field name="minutes" :label="__('moderation.admin.minutes')"><x-ui.input name="minutes" type="number" /></x-ui.field>
            <div class="sm:col-span-3"><x-ui.button type="submit" size="sm" icon="terminal">{{ __('moderation.admin.send_command') }}</x-ui.button></div>
        </form>
    </x-admin.card>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('moderation.admin.command_type') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('moderation.admin.target') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('admin.users.columns.status') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('moderation.admin.issued_by') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium"><span class="sr-only">{{ __('moderation.admin.cancel_command') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($commands as $c)
                        <tr class="h-11">
                            <td class="px-4 font-mono text-xs text-text">{{ $c->type }}</td>
                            <td class="px-4 text-text-muted">{{ $c->target?->account_name ?? '—' }}</td>
                            <td class="px-4"><x-ui.badge :tone="match ($c->status) { 'succeeded' => 'success', 'failed' => 'danger', 'pending', 'dispatched' => 'amber', default => 'neutral' }">{{ __('moderation.admin.command_status.'.$c->status) }}</x-ui.badge>
                                @if (is_array($c->result) && isset($c->result['error']))<span class="text-xs text-danger">{{ $c->result['error'] }}</span>@endif <span class="text-xs text-text-subtle">· {{ $c->attempts }}×</span></td>
                            <td class="px-4 text-text-muted">{{ $c->requester?->username ?? __('support.system') }}</td>
                            <td class="px-4">@if ($c->status === 'pending')<form method="POST" action="{{ route('admin.game-commands.cancel', $c->ulid) }}">@csrf<x-ui.button type="submit" size="sm" variant="ghost">{{ __('moderation.admin.cancel_command') }}</x-ui.button></form>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('moderation.admin.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $commands->links() }}</div>
</x-layouts.admin>
