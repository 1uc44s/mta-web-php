{{-- İtirazlar — docs/ROUTES.md §6.7. --}}
<x-layouts.admin :title="__('moderation.admin.appeals')">
    <x-admin.header :title="__('moderation.admin.appeals')" />
    <nav aria-label="Durum" class="mb-4 flex gap-1 border-b border-border">
        @foreach (['open' => __('applications.admin.filter.open'), 'all' => __('admin.all')] as $value => $label)
            <a href="{{ route('admin.appeals.index', ['status' => $value]) }}" @if ($status === $value) aria-current="page" @endif
               class="-mb-px border-b-2 border-transparent px-3 py-2 text-sm text-text-muted hover:text-text aria-[current=page]:border-accent aria-[current=page]:text-text">{{ $label }}</a>
        @endforeach
    </nav>
    <x-admin.card flush>
        <ul class="divide-y divide-border">
            @forelse ($appeals as $a)
                <li><a href="{{ route('admin.appeals.show', $a) }}" class="flex flex-wrap items-center gap-3 px-4 py-3 hover:bg-surface-2/60">
                    <span class="font-medium text-text">{{ $a->user?->username }}</span>
                    <span class="text-sm text-text-muted">{{ __('moderation.types.'.$a->sanction->type) }}</span>
                    <x-ui.badge>{{ __('moderation.appeal_statuses.'.$a->status) }}</x-ui.badge>
                    <span class="ms-auto text-xs text-text-subtle">{{ $a->created_at?->diffForHumans() }}</span>
                </a></li>
            @empty
                <li class="px-4 py-10 text-center text-text-muted">{{ __('moderation.admin.empty') }}</li>
            @endforelse
        </ul>
    </x-admin.card>
    <div class="mt-4">{{ $appeals->links() }}</div>
</x-layouts.admin>
