{{-- Ödeme dönüş sayfası — docs/SRS.md FR-PAY-004: bakiye yüklemez; durum `pending` ise 5 sn'de bir yenilenir. --}}
<x-layouts.panel :title="__('economy.order').' '.$order->order_number">
    <div class="mx-auto max-w-2xl space-y-6">
        <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('economy.order') }} <span class="font-mono">{{ $order->order_number }}</span></h1>
        <div class="rounded-lg border border-border bg-surface-1 p-6"
             @if ($order->isPending()) data-payment-status-url="{{ route('panel.payments.status', $order) }}" @endif>
            <p class="flex items-center gap-3">
                <x-ui.badge :tone="match ($order->status) { 'succeeded' => 'success', 'failed', 'cancelled' => 'danger', default => 'amber' }">{{ __('economy.order_status.'.$order->status) }}</x-ui.badge>
                <span class="text-text-muted">{{ $order->formattedAmount() }}</span>
            </p>
            <p class="mt-4 text-text" role="status" aria-live="polite">
                @if ($order->isPending())
                    {{ __('economy.return.pending') }}
                @elseif ($order->status === 'succeeded' || in_array($order->status, ['refunded', 'partially_refunded'], true))
                    {{ __('economy.return.succeeded', ['amount' => number_format($order->credit_amount, 0, ',', '.'), 'unit' => $order->wallet->type->unit_label]) }}
                @else
                    {{ __('economy.return.failed') }}
                @endif
            </p>
        </div>
        <x-ui.button variant="secondary" icon="wallet" :href="route('panel.wallet')">{{ __('economy.wallet') }}</x-ui.button>
    </div>
</x-layouts.panel>
