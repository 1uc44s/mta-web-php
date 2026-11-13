{{-- Sahte ödeme ekranı — yalnızca geliştirme (FakeProvider, docs/ROUTES.md §5.7). --}}
<x-layouts.auth :title="__('economy.fake.title')">
    <div class="space-y-6">
        <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('economy.fake.title') }}</h1>
        <p role="alert" class="rounded-md border border-amber/40 bg-amber/10 px-4 py-3 text-sm text-text">{{ __('economy.fake.warning') }}</p>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-text-subtle">{{ __('economy.order') }}</dt><dd class="font-mono text-text">{{ $order->order_number }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-text-subtle">Tutar</dt><dd class="text-text">{{ $order->formattedAmount() }}</dd></div>
        </dl>
        <form method="POST" action="{{ $completeUrl }}" class="flex flex-wrap gap-3">
            @csrf
            <x-ui.button type="submit" name="outcome" value="succeeded" icon="check">{{ __('economy.fake.pay') }}</x-ui.button>
            <x-ui.button type="submit" name="outcome" value="failed" variant="secondary">{{ __('economy.fake.fail') }}</x-ui.button>
        </form>
    </div>
</x-layouts.auth>
