{{-- Ledger — docs/ROUTES.md §6.7 `admin.economy.transactions` (cursor pagination, salt okunur). --}}
<x-layouts.admin :title="__('economy.transactions')">
    <x-admin.header :title="__('economy.transactions')" :back="route('admin.economy.index')" :back-label="__('economy.admin.title')" />

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <div class="min-w-64 flex-1">
            <label for="q" class="sr-only">{{ __('admin.search') }}</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="{{ __('economy.admin.user') }} / ULID" class="h-10 w-full rounded-md border border-border-input bg-surface-1 px-3 text-sm text-text">
        </div>
        <x-ui.button type="submit" size="sm" variant="secondary" icon="search">{{ __('admin.filter') }}</x-ui.button>
    </form>

    @include('admin.economy.partials.ledger', ['transactions' => $transactions, 'showUser' => true])
    <div class="mt-4">{{ $transactions->links() }}</div>
</x-layouts.admin>
