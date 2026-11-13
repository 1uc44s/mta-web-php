{{-- Kredi yükle — docs/SRS.md FR-PAY-002. Tutar sunucuda paketten alınır; form yalnızca paket kimliği gönderir. --}}
<x-layouts.panel :title="__('economy.top_up')">
    <div class="mx-auto max-w-4xl space-y-6">
        <header>
            <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('economy.top_up') }}</h1>
            <p class="mt-2 max-w-2xl text-text-muted">{{ __('economy.top_up_intro') }}</p>
        </header>
        @error('package_id')<p role="alert" class="text-sm text-danger">{{ $message }}</p>@enderror

        <ul class="grid gap-4 sm:grid-cols-3">
            @forelse ($packages as $p)
                <li class="flex flex-col rounded-lg border border-border bg-surface-1 p-6">
                    <p class="font-display text-h4 font-semibold text-text">{{ $p->name }}</p>
                    <p class="mt-3 font-mono text-h2 font-bold text-text">{{ number_format($p->credit_amount, 0, ',', '.') }} <span class="text-h4 text-text-muted">{{ $p->walletType->unit_label }}</span></p>
                    @if ($p->bonus_amount > 0)<p class="text-sm text-success">{{ __('economy.bonus', ['amount' => number_format($p->bonus_amount, 0, ',', '.')]) }}</p>@endif
                    <p class="mt-4 text-text-muted">{{ number_format($p->price_minor / 100, 2, ',', '.') }} {{ $p->currency }}</p>
                    <form method="POST" action="{{ route('panel.payments.checkout') }}" class="mt-auto pt-5">
                        @csrf
                        <input type="hidden" name="package_id" value="{{ $p->id }}">
                        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                        <x-ui.button type="submit" class="w-full" icon="credit-card">{{ __('economy.buy') }}</x-ui.button>
                    </form>
                </li>
            @empty
                <li class="sm:col-span-3"><x-ui.empty-state :title="__('economy.no_packages')" icon="package" /></li>
            @endforelse
        </ul>
        <p class="text-sm text-text-subtle"><a class="underline" href="{{ route('public.legal.refund-policy') }}">{{ __('economy.refund_policy') }}</a></p>
    </div>
</x-layouts.panel>
