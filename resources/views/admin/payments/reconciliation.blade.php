{{-- Mutabakat — docs/SRS.md FR-WAL-007, DATABASE §6.9. --}}
<x-layouts.admin :title="__('economy.admin.reconciliation')">
    <x-admin.header :title="__('economy.admin.reconciliation')" />

    <form method="POST" action="{{ route('admin.payments.reconciliation.run') }}" class="mb-6">@csrf<x-ui.button type="submit" size="sm" icon="calculator">{{ __('economy.admin.run') }}</x-ui.button></form>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('economy.admin.last_run') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('economy.admin.checked') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('economy.admin.mismatches') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('economy.admin.total_balance') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">Ledger</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($runs as $run)
                        <tr class="align-top">
                            <td class="px-4 py-2 text-text-muted">{{ $run->started_at->format('d.m.Y H:i') }}<br><span class="text-xs">{{ $run->trigger?->username ?? 'zamanlayıcı' }}</span></td>
                            <td class="px-4 py-2 text-text">{{ $run->wallets_checked }}</td>
                            <td class="px-4 py-2">
                                @if ($run->mismatches === 0)
                                    <x-ui.badge tone="success">0</x-ui.badge>
                                @else
                                    <x-ui.badge tone="danger">{{ $run->mismatches }}</x-ui.badge>
                                    <ul class="mt-1 font-mono text-xs text-danger">
                                        @foreach ($run->details ?? [] as $d)<li>#{{ $d['wallet_id'] }}: {{ $d['balance'] }} ≠ {{ $d['ledger_net'] }}</li>@endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="px-4 py-2 font-mono text-text-muted">{{ number_format($run->total_balance, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 font-mono text-text-muted">{{ number_format($run->ledger_net, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('economy.admin.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
</x-layouts.admin>
