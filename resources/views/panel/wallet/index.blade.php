{{-- Cüzdanım — docs/ROUTES.md §5.7 (F:wallet). Türlere göre ayrı kartlar. --}}
<x-layouts.panel :title="__('economy.wallet')">
    <div class="mx-auto max-w-4xl space-y-8">
        <header class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('economy.wallet') }}</h1>
            @if ($paymentsEnabled)
                <x-ui.button icon="credit-card" :href="route('panel.top-up')">{{ __('economy.top_up') }}</x-ui.button>
            @endif
        </header>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($types as $type)
                <div class="rounded-lg border border-border bg-surface-1 p-6">
                    <p class="text-sm text-text-subtle">{{ $type->name }}</p>
                    <p class="mt-2 font-mono text-h2 font-bold text-text">{{ number_format($wallets[$type->id]->balance ?? 0, 0, ',', '.') }} <span class="text-h4 text-text-muted">{{ $type->unit_label }}</span></p>
                </div>
            @endforeach
        </div>

        <section aria-labelledby="h-recent">
            <div class="flex items-center justify-between gap-3">
                <h2 id="h-recent" class="font-display text-h4 font-semibold text-text">{{ __('economy.transactions') }}</h2>
                <a href="{{ route('panel.transactions') }}" class="text-sm text-accent underline">{{ __('admin.all') }}</a>
            </div>
            @include('panel.wallet.partials.transaction-list', ['transactions' => $recent])
        </section>
    </div>
</x-layouts.panel>
