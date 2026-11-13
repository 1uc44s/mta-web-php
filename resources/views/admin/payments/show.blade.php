{{-- Sipariş detayı — webhook geçmişi ve iadeler dahil (docs/ROUTES.md §6.7). --}}
<x-layouts.admin :title="$order->order_number">
    <x-admin.header :title="$order->order_number" :description="$order->user->username.' · '.$order->formattedAmount().' · '.__('economy.order_status.'.$order->status)" :back="route('admin.payments.index')" :back-label="__('economy.admin.payments')" />

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
            <x-admin.card>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-text-subtle">Paket</dt><dd class="text-text">{{ $order->package?->name ?? '—' }}</dd></div>
                    <div><dt class="text-text-subtle">Kredi</dt><dd class="text-text">{{ number_format($order->credit_amount, 0, ',', '.') }}</dd></div>
                    <div><dt class="text-text-subtle">Sağlayıcı</dt><dd class="font-mono text-text">{{ $order->provider }} · {{ $order->provider_order_id ?? '—' }}</dd></div>
                    <div><dt class="text-text-subtle">İade edilen</dt><dd class="text-text">{{ number_format($order->refunded_minor / 100, 2, ',', '.') }} {{ $order->currency }}</dd></div>
                    <div><dt class="text-text-subtle">Ödeme</dt><dd class="text-text">{{ $order->paid_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
                    @if ($order->failure_reason)<div><dt class="text-text-subtle">Hata</dt><dd class="text-danger">{{ $order->failure_reason }}</dd></div>@endif
                </dl>
            </x-admin.card>

            <x-admin.card :title="__('economy.admin.webhooks')">
                <ul class="divide-y divide-border text-sm">
                    @forelse ($order->events as $e)
                        <li class="flex flex-wrap items-center gap-3 py-2">
                            <span class="font-mono text-xs text-text">{{ $e->event_type }}</span>
                            <x-ui.badge :tone="$e->status === 'processed' ? 'success' : ($e->status === 'failed' ? 'danger' : 'neutral')">{{ $e->status }}</x-ui.badge>
                            @if ($e->error)<span class="text-xs text-danger">{{ $e->error }}</span>@endif
                            <span class="ms-auto font-mono text-xs text-text-subtle">{{ $e->provider_event_id }} · {{ $e->received_at->format('d.m.Y H:i:s') }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-text-muted">—</li>
                    @endforelse
                </ul>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card :title="__('economy.admin.refunds')">
                <ul class="space-y-2 text-sm">
                    @forelse ($order->refunds as $rf)
                        <li>{{ number_format($rf->amount_minor / 100, 2, ',', '.') }} · <x-ui.badge>{{ __('economy.admin.refund_status.'.$rf->status) }}</x-ui.badge>
                            <span class="text-xs text-text-subtle">{{ $rf->requester?->username }} → {{ $rf->approver?->username ?? '—' }}</span></li>
                    @empty
                        <li class="text-text-muted">—</li>
                    @endforelse
                </ul>
                @can('payments.refund.request')
                    @if (in_array($order->status, ['succeeded', 'partially_refunded'], true))
                        <form method="POST" action="{{ route('admin.payments.refund-request', $order) }}" class="mt-4 space-y-3">
                            @csrf
                            <x-ui.field name="amount_minor" :label="__('economy.admin.refund_amount')" required><x-ui.input name="amount_minor" type="number" :value="$order->amount_minor - $order->refunded_minor" required /></x-ui.field>
                            <x-ui.field name="reason" :label="__('admin.reason')" required><x-ui.input name="reason" multiline rows="2" required /></x-ui.field>
                            <x-ui.button type="submit" size="sm" variant="secondary">{{ __('economy.admin.refund') }}</x-ui.button>
                        </form>
                    @endif
                @endcan
            </x-admin.card>
        </div>
    </div>
</x-layouts.admin>
