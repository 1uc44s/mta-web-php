{{-- Destek taleplerim — docs/ROUTES.md §5.4. --}}
<x-layouts.panel :title="__('support.my_tickets')">
    <div class="mx-auto max-w-4xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('support.my_tickets') }}</h1>
            <x-ui.button icon="plus" :href="route('panel.support.create')">{{ __('support.create') }}</x-ui.button>
        </div>

        <nav aria-label="Durum" class="mt-6 flex gap-1 border-b border-border">
            @foreach (__('support.filter') as $value => $label)
                <a href="{{ route('panel.support.index', ['status' => $value]) }}" @if ($filter === $value) aria-current="page" @endif
                   class="-mb-px border-b-2 border-transparent px-3 py-2 text-sm text-text-muted hover:text-text aria-[current=page]:border-accent aria-[current=page]:text-text">{{ $label }}</a>
            @endforeach
        </nav>

        <ul class="mt-4 space-y-3">
            @forelse ($tickets as $t)
                <li>
                    <a href="{{ route('panel.support.show', $t) }}" class="flex flex-wrap items-center gap-3 rounded-lg border border-border bg-surface-1 px-5 py-4 hover:border-border-strong">
                        @if ($t->player_unread)<span class="size-2 rounded-full bg-accent" aria-label="Yeni yanıt"></span>@endif
                        <span class="font-mono text-xs text-text-subtle">{{ $t->number }}</span>
                        <span class="min-w-0 flex-1 truncate font-medium text-text">{{ $t->subject }}</span>
                        <x-ui.badge :tone="$t->status === 'waiting_for_player' ? 'amber' : ($t->status === 'closed' ? 'neutral' : 'accent')">{{ __('support.status.'.$t->status) }}</x-ui.badge>
                        <time class="text-xs text-text-subtle" datetime="{{ $t->updated_at?->toIso8601String() }}">{{ $t->updated_at?->diffForHumans() }}</time>
                    </a>
                </li>
            @empty
                <li><x-ui.empty-state :title="__('support.empty')" icon="life-buoy" /></li>
            @endforelse
        </ul>
        <div class="mt-4">{{ $tickets->links() }}</div>
    </div>
</x-layouts.panel>
