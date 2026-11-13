{{-- Ekonomi özeti — docs/ROUTES.md §6.7 `admin.economy.index` (günlük/aylık toplamlar). --}}
<x-layouts.admin :title="__('economy.admin.title')">
    <x-admin.header :title="__('economy.admin.title')" />

    @foreach (['today', 'month'] as $period)
        <h2 class="mb-3 mt-6 text-sm font-medium text-text-subtle">{{ __('economy.admin.'.$period) }}</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <x-admin.kpi :label="__('economy.admin.credits_in')" :value="number_format($stats[$period]['in'], 0, ',', '.')" icon="arrow-down-left" />
            <x-admin.kpi :label="__('economy.admin.credits_out')" :value="number_format($stats[$period]['out'], 0, ',', '.')" icon="arrow-up-right" />
            <x-admin.kpi :label="__('economy.admin.revenue')" :value="number_format($stats[$period]['revenue'] / 100, 2, ',', '.').' TRY'" icon="credit-card" />
        </div>
    @endforeach

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <x-admin.kpi :label="__('economy.admin.total_balance')" :value="number_format($totalBalance, 0, ',', '.')" icon="wallet" />
        <x-admin.kpi :label="__('economy.admin.wallets')" :value="(string) $walletCount" icon="users" />
        <x-admin.kpi :label="__('economy.admin.adjustments')" :value="(string) $pendingAdjustments" icon="scale" />
    </div>

    <p class="mt-6 flex flex-wrap gap-3">
        <x-ui.button size="sm" variant="secondary" :href="route('admin.economy.transactions')">{{ __('economy.transactions') }}</x-ui.button>
        <x-ui.button size="sm" variant="secondary" :href="route('admin.economy.adjustments.index')">{{ __('economy.admin.adjustments') }}</x-ui.button>
    </p>
</x-layouts.admin>
