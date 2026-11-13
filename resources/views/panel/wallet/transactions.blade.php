{{-- İşlemlerim — docs/ROUTES.md §5.7 (filtre: yön). --}}
<x-layouts.panel :title="__('economy.transactions')">
    <div class="mx-auto max-w-4xl">
        <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('economy.transactions') }}</h1>
        <nav aria-label="Yön" class="mt-6 flex gap-1 border-b border-border">
            @foreach (__('economy.filter') as $value => $label)
                <a href="{{ route('panel.transactions', ['direction' => $value]) }}" @if ($direction === $value) aria-current="page" @endif
                   class="-mb-px border-b-2 border-transparent px-3 py-2 text-sm text-text-muted hover:text-text aria-[current=page]:border-accent aria-[current=page]:text-text">{{ $label }}</a>
            @endforeach
        </nav>
        @include('panel.wallet.partials.transaction-list', ['transactions' => $transactions])
        <div class="mt-4">{{ $transactions->links() }}</div>
    </div>
</x-layouts.panel>
