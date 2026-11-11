{{-- İletişim mesajları — docs/ROUTES.md §6.9. E-posta adresleri yalnızca detayda gösterilir. --}}
@php $labels = ['new' => 'Yeni', 'read' => 'Okundu', 'replied' => 'Yanıtlandı', 'spam' => 'Spam']; @endphp
<x-layouts.admin :title="__('content_admin.nav.contact')">
    <x-admin.header :title="__('content_admin.nav.contact')" />
    <nav aria-label="Durum" class="mb-4 flex flex-wrap gap-1 border-b border-border">
        @foreach (['' => 'Tümü'] + $labels as $value => $label)
            <a href="{{ route('admin.contact.index', array_filter(['status' => $value ?: null])) }}" @if ($status === $value) aria-current="page" @endif
               class="-mb-px border-b-2 border-transparent px-3 py-2 text-sm text-text-muted hover:text-text aria-[current=page]:border-accent aria-[current=page]:text-text">{{ $label }}</a>
        @endforeach
    </nav>
    <x-admin.card flush>
        <ul class="divide-y divide-border">
            @forelse ($messages as $m)
                <li class="flex flex-wrap items-center gap-3 px-4 py-3">
                    <x-ui.badge :tone="$m->status === 'new' ? 'accent' : 'neutral'">{{ $labels[$m->status] ?? $m->status }}</x-ui.badge>
                    <a href="{{ route('admin.contact.show', $m) }}" class="font-medium text-text hover:text-accent">{{ $m->subject }}</a>
                    <span class="text-text-muted">{{ $m->name }}</span>
                    <time class="ml-auto text-xs text-text-subtle" datetime="{{ $m->created_at?->toIso8601String() }}">{{ $m->created_at?->diffForHumans() }}</time>
                </li>
            @empty
                <li class="px-4 py-10 text-center text-text-muted">{{ __('content_admin.empty') }}</li>
            @endforelse
        </ul>
    </x-admin.card>
    <div class="mt-4">{{ $messages->links() }}</div>
</x-layouts.admin>
