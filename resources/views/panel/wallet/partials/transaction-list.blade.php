{{-- Ledger kayıtları (oyuncu görünümü): işlem no, açıklama, yön, tutar, sonrası bakiye. --}}
<ul class="mt-4 divide-y divide-border rounded-lg border border-border bg-surface-1">
    @forelse ($transactions as $t)
        <li class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm">
            <span @class(['font-mono font-semibold', 'text-success' => $t->direction === 'credit', 'text-danger' => $t->direction === 'debit'])>
                {{ $t->direction === 'credit' ? '+' : '−' }}{{ number_format($t->amount, 0, ',', '.') }}
            </span>
            <span class="min-w-0 flex-1 truncate text-text">{{ $t->description }}</span>
            <x-ui.badge>{{ __('economy.reason.'.$t->reason) }}</x-ui.badge>
            <span class="font-mono text-xs text-text-subtle">{{ number_format($t->balance_after, 0, ',', '.') }}</span>
            <time class="text-xs text-text-subtle" datetime="{{ $t->created_at->toIso8601String() }}">{{ $t->created_at->format('d.m.Y H:i') }}</time>
        </li>
    @empty
        <li class="px-5 py-8 text-center text-sm text-text-muted">{{ __('economy.no_transactions') }}</li>
    @endforelse
</ul>
