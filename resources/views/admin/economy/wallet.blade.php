{{-- Cüzdan detayı — docs/ROUTES.md §6.7 `admin.economy.wallets.show`. --}}
<x-layouts.admin :title="$wallet->user->username.' · '.$wallet->type->name">
    <x-admin.header :title="$wallet->user->username" :description="$wallet->type->name.' · '.number_format($wallet->balance, 0, ',', '.').' '.$wallet->type->unit_label.' · v'.$wallet->version" :back="route('admin.economy.transactions')" :back-label="__('economy.transactions')" />
    @include('admin.economy.partials.ledger', ['transactions' => $transactions, 'showUser' => false])
    <div class="mt-4">{{ $transactions->links() }}</div>
</x-layouts.admin>
