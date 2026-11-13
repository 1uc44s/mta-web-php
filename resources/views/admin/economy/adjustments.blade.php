{{-- Düzeltme talepleri — docs/SRS.md FR-WAL-006 (dört göz). --}}
<x-layouts.admin :title="__('economy.admin.adjustments')">
    <x-admin.header :title="__('economy.admin.adjustments')" />

    @can('wallet.adjust.request')
        <x-admin.card :title="__('economy.admin.new_adjustment')" class="mb-6">
            <form method="POST" action="{{ route('admin.economy.adjustments.store') }}" class="grid gap-3 sm:grid-cols-3">
                @csrf
                <x-ui.field name="username" :label="__('economy.admin.user')" required><x-ui.input name="username" required /></x-ui.field>
                <x-ui.field name="direction" :label="__('economy.admin.direction')" required>
                    <select id="f-direction" name="direction" class="h-10 rounded-sm border border-border-input bg-surface-2 px-3 text-sm text-text">
                        @foreach (__('economy.direction') as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </x-ui.field>
                <x-ui.field name="amount" :label="__('economy.admin.amount')" required><x-ui.input name="amount" type="number" required /></x-ui.field>
                <x-ui.field name="reason" :label="__('economy.admin.reason')" class="sm:col-span-3" required><x-ui.input name="reason" multiline rows="2" required /></x-ui.field>
                <div class="sm:col-span-3"><x-ui.button type="submit" size="sm">{{ __('economy.admin.request') }}</x-ui.button></div>
            </form>
        </x-admin.card>
    @endcan

    <nav aria-label="Durum" class="mb-4 flex gap-1 border-b border-border">
        @foreach (['pending' => __('economy.admin.adjustment_status.pending'), 'applied' => __('economy.admin.adjustment_status.applied'), 'rejected' => __('economy.admin.adjustment_status.rejected'), 'all' => __('admin.all')] as $value => $label)
            <a href="{{ route('admin.economy.adjustments.index', ['status' => $value]) }}" @if ($status === $value) aria-current="page" @endif
               class="-mb-px border-b-2 border-transparent px-3 py-2 text-sm text-text-muted hover:text-text aria-[current=page]:border-accent aria-[current=page]:text-text">{{ $label }}</a>
        @endforeach
    </nav>

    @error('review_note')<p role="alert" class="mb-3 text-sm text-danger">{{ $message }}</p>@enderror

    <ul class="space-y-3">
        @forelse ($requests as $r)
            <li class="rounded-lg border border-border bg-surface-1 p-4">
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <span @class(['font-mono font-semibold', 'text-success' => $r->direction === 'credit', 'text-danger' => $r->direction === 'debit'])>{{ $r->direction === 'credit' ? '+' : '−' }}{{ number_format($r->amount, 0, ',', '.') }}</span>
                    <span class="text-text">{{ $r->wallet?->user?->username }}</span>
                    @if ($r->reversalOf)<x-ui.badge tone="info">{{ __('economy.admin.reversal') }} · {{ $r->reversalOf->ulid }}</x-ui.badge>@endif
                    @if ($r->requires_second_approval)<x-ui.badge tone="amber">{{ __('economy.admin.four_eyes') }}</x-ui.badge>@endif
                    <x-ui.badge>{{ __('economy.admin.adjustment_status.'.$r->status) }}</x-ui.badge>
                    <span class="ms-auto text-xs text-text-subtle">{{ __('economy.admin.requested_by') }}: {{ $r->requester?->username }} · {{ $r->created_at?->diffForHumans() }}</span>
                </div>
                <p class="mt-2 text-sm text-text-muted">{{ $r->reason }}</p>
                @if ($r->review_note)<p class="mt-1 text-xs text-text-subtle">{{ $r->reviewer?->username }}: {{ $r->review_note }}</p>@endif
                @if ($r->status === 'pending')
                    @can('wallet.adjust.approve')
                        <form method="POST" class="mt-3 flex flex-wrap items-center gap-2">
                            @csrf
                            <label for="note-{{ $r->id }}" class="sr-only">{{ __('economy.admin.review_note') }}</label>
                            <input id="note-{{ $r->id }}" name="review_note" placeholder="{{ __('economy.admin.review_note') }}" class="h-8 w-56 rounded-md border border-border-input bg-surface-2 px-2 text-xs text-text">
                            <input type="hidden" name="reason" value="{{ __('economy.admin.reject') }}: {{ $r->ulid }}">
                            <x-ui.button type="submit" size="sm" formaction="{{ route('admin.economy.adjustments.approve', $r->ulid) }}">{{ __('economy.admin.approve') }}</x-ui.button>
                            <x-ui.button type="submit" size="sm" variant="ghost" formaction="{{ route('admin.economy.adjustments.reject', $r->ulid) }}">{{ __('economy.admin.reject') }}</x-ui.button>
                        </form>
                    @endcan
                @endif
            </li>
        @empty
            <li class="rounded-lg border border-border bg-surface-1 px-4 py-10 text-center text-sm text-text-muted">{{ __('economy.admin.empty') }}</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-layouts.admin>
