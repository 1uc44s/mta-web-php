{{-- İmzalı önizleme (30 dk) — docs/ROUTES.md §6.8. Markdown alanları sitedeki prose biçimiyle render edilir. --}}
<x-layouts.admin :title="__('content_admin.preview_title', ['title' => $title])">
    <x-admin.header :title="$title" :description="__('content_admin.preview_note')" />
    <div class="space-y-8">
        @forelse ($blocks as $block)
            <x-admin.card :title="$block['label']">
                <x-ui.prose :html="$block['html']" />
            </x-admin.card>
        @empty
            <p class="text-text-muted">{{ __('content_admin.empty') }}</p>
        @endforelse
    </div>
</x-layouts.admin>
