{{-- Başvurular — docs/ROUTES.md §5.5, SRS FR-APP-001/004. --}}
<x-layouts.panel :title="__('applications.title')">
    <div class="mx-auto max-w-4xl space-y-10">
        <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('applications.title') }}</h1>

        <section aria-labelledby="h-open">
            <h2 id="h-open" class="font-display text-h4 font-semibold text-text">{{ __('applications.open_types') }}</h2>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @forelse ($types as $row)
                    @php($t = $row['type'])
                    <li class="flex flex-col rounded-lg border border-border bg-surface-1 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-display text-h4 font-semibold text-text">{{ $t->name }}</span>
                            <x-ui.badge tone="accent">{{ __('applications.kinds.'.$t->kind) }}</x-ui.badge>
                        </div>
                        @if ($t->description)<p class="mt-2 text-sm text-text-muted">{{ $t->description }}</p>@endif
                        <ul class="mt-3 flex flex-wrap gap-2 text-xs text-text-subtle">
                            @if ($t->requires_linked_account)<li>· {{ __('applications.req.link') }}</li>@endif
                            @if ($t->requires_character)<li>· {{ __('applications.req.character') }}</li>@endif
                            @if ($t->min_account_age_days)<li>· {{ __('applications.req.age', ['days' => $t->min_account_age_days]) }}</li>@endif
                        </ul>
                        <div class="mt-4">
                            @if ($row['blocked'])
                                <p class="text-sm text-text-muted">{{ $row['blocked'] }}</p>
                            @else
                                <x-ui.button size="sm" icon="clipboard-list" :href="route('panel.applications.create', $t->slug)">{{ __('applications.apply') }}</x-ui.button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="sm:col-span-2"><x-ui.empty-state :title="__('applications.no_open_types')" icon="clipboard-list" /></li>
                @endforelse
            </ul>
        </section>

        <section aria-labelledby="h-mine">
            <h2 id="h-mine" class="font-display text-h4 font-semibold text-text">{{ __('applications.mine') }}</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($applications as $a)
                    <li>
                        <a href="{{ route('panel.applications.show', $a) }}" class="flex flex-wrap items-center gap-3 rounded-lg border border-border bg-surface-1 px-5 py-4 hover:border-border-strong">
                            <span class="min-w-0 flex-1 truncate font-medium text-text">{{ $a->type?->name }}</span>
                            <x-ui.badge :tone="match ($a->status) { 'approved' => 'success', 'rejected' => 'danger', 'needs_info' => 'amber', 'cancelled' => 'neutral', default => 'accent' }">{{ __('applications.status.'.$a->status) }}</x-ui.badge>
                            <time class="text-xs text-text-subtle" datetime="{{ $a->updated_at?->toIso8601String() }}">{{ $a->updated_at?->diffForHumans() }}</time>
                        </a>
                    </li>
                @empty
                    <li><x-ui.empty-state :title="__('applications.empty')" icon="clipboard-list" /></li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.panel>
