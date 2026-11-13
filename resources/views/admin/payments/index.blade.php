{{-- Ödemeler — docs/ROUTES.md §6.7 `admin.payments.index`. --}}
<x-layouts.admin :title="__('economy.admin.payments')">
    <x-admin.header :title="__('economy.admin.payments')" />

    @if ($pendingRefunds->isNotEmpty())
        <x-admin.card :title="__('economy.admin.refunds')" class="mb-6">
            @error('refund')<p role="alert" class="mb-2 text-sm text-danger">{{ $message }}</p>@enderror
            <ul class="divide-y divide-border">
                @foreach ($pendingRefunds as $rf)
                    <li class="flex flex-wrap items-center gap-3 py-2 text-sm">
                        <a class="font-mono text-text hover:text-accent" href="{{ route('admin.payments.show', $rf->order->ulid) }}">{{ $rf->order->order_number }}</a>
                        <span class="text-text">{{ number_format($rf->amount_minor / 100, 2, ',', '.') }}</span>
                        <x-ui.badge tone="amber">{{ __('economy.admin.refund_status.'.$rf->status) }}</x-ui.badge>
                        <span class="text-xs text-text-subtle">{{ __('economy.admin.requested_by') }}: {{ $rf->requester?->username }}</span>
                        @can('payments.refund.approve')
                            <form method="POST" class="ms-auto flex gap-2">
                                @csrf
                                <input type="hidden" name="reason" value="{{ __('economy.admin.reject') }}: {{ $rf->ulid }}">
                                @if ($rf->status === 'requested')<x-ui.button type="submit" size="sm" formaction="{{ route('admin.refunds.approve', $rf->ulid) }}">{{ __('economy.admin.approve') }}</x-ui.button>@endif
                                <x-ui.button type="submit" size="sm" variant="ghost" formaction="{{ route('admin.refunds.reject', $rf->ulid) }}">{{ __('economy.admin.reject') }}</x-ui.button>
                            </form>
                        @endcan
                    </li>
                @endforeach
            </ul>
        </x-admin.card>
    @endif

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <div class="min-w-64 flex-1">
            <label for="q" class="sr-only">{{ __('admin.search') }}</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="MRP-… / {{ __('economy.admin.user') }}" class="h-10 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
        </div>
        <label for="status" class="sr-only">{{ __('admin.filter') }}</label>
        <select id="status" name="status" class="h-10 rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
            <option value="">{{ __('admin.all') }}</option>
            @foreach (__('economy.order_status') as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
        </select>
        <x-ui.button type="submit" size="sm" variant="secondary" icon="search">{{ __('admin.filter') }}</x-ui.button>
    </form>

    <x-admin.card flush>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border text-xs text-text-subtle">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('economy.order') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('economy.admin.user') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">Tutar</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('admin.users.columns.status') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">Tarih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($orders as $o)
                        <tr class="h-11 hover:bg-surface-2/60">
                            <td class="px-4"><a href="{{ route('admin.payments.show', $o) }}" class="font-mono text-text hover:text-accent">{{ $o->order_number }}</a></td>
                            <td class="px-4 text-text-muted">{{ $o->user?->username }}</td>
                            <td class="px-4 text-text-muted">{{ $o->formattedAmount() }}</td>
                            <td class="px-4"><x-ui.badge :tone="match ($o->status) { 'succeeded' => 'success', 'failed' => 'danger', 'refunded', 'partially_refunded' => 'info', default => 'amber' }">{{ __('economy.order_status.'.$o->status) }}</x-ui.badge></td>
                            <td class="px-4 text-text-muted">{{ $o->created_at?->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-text-muted">{{ __('economy.admin.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-layouts.admin>
