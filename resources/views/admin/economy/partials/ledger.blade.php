{{-- Ledger tablosu (ACP). Ters kayıt talebi dört göz akışına girer (admin.economy.reversal-request). --}}
<x-admin.card flush>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-border text-xs text-text-subtle">
                <tr>
                    <th scope="col" class="px-4 py-2.5 font-medium">ULID</th>
                    @if ($showUser)<th scope="col" class="px-4 py-2.5 font-medium">{{ __('economy.admin.user') }}</th>@endif
                    <th scope="col" class="px-4 py-2.5 font-medium">{{ __('economy.admin.amount') }}</th>
                    <th scope="col" class="px-4 py-2.5 font-medium">{{ __('economy.balance') }}</th>
                    <th scope="col" class="px-4 py-2.5 font-medium">{{ __('economy.admin.reason') }}</th>
                    <th scope="col" class="px-4 py-2.5 font-medium"><span class="sr-only">{{ __('economy.admin.reversal') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($transactions as $t)
                    <tr class="align-top">
                        <td class="px-4 py-2 font-mono text-xs text-text-muted">{{ $t->ulid }}<br>{{ $t->created_at->format('d.m.Y H:i') }}</td>
                        @if ($showUser)<td class="px-4 py-2"><a class="text-text hover:text-accent" href="{{ route('admin.economy.wallets.show', $t->wallet_id) }}">{{ $t->wallet?->user?->username }}</a></td>@endif
                        <td @class(['px-4 py-2 font-mono', 'text-success' => $t->direction === 'credit', 'text-danger' => $t->direction === 'debit'])>{{ $t->direction === 'credit' ? '+' : '−' }}{{ number_format($t->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-text-muted">{{ number_format($t->balance_before, 0, ',', '.') }} → {{ number_format($t->balance_after, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-text-muted"><x-ui.badge>{{ __('economy.reason.'.$t->reason) }}</x-ui.badge> {{ $t->description }}</td>
                        <td class="px-4 py-2">
                            @can('wallet.adjust.request')
                                @if ($t->reason !== 'reversal')
                                    <details>
                                        <summary class="cursor-pointer text-xs text-text-muted hover:text-text">{{ __('economy.admin.reversal') }}</summary>
                                        <form method="POST" action="{{ route('admin.economy.reversal-request', $t->ulid) }}" class="mt-2 flex flex-col gap-2">
                                            @csrf
                                            <label for="rv-{{ $t->id }}" class="sr-only">{{ __('economy.admin.reason') }}</label>
                                            <input id="rv-{{ $t->id }}" name="reason" required minlength="20" placeholder="{{ __('economy.admin.reason') }}" class="h-8 w-56 rounded-md border border-border-input bg-surface-2 px-2 text-xs text-text">
                                            <x-ui.button type="submit" size="sm" variant="secondary">{{ __('economy.admin.request') }}</x-ui.button>
                                        </form>
                                    </details>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-text-muted">{{ __('economy.admin.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin.card>
