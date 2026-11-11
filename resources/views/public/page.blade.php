{{-- Hakkımızda, yasal ve ek sayfalar (template: standard | legal). --}}
@php($isLegal = $page->template === 'legal')
<x-layouts.public :title="$page->seo_title ?: $page->title" :description="$description">
    <x-ui.page-header :eyebrow="$isLegal ? __('public.pages.legal.eyebrow') : __('public.pages.about.eyebrow')" :title="$page->title" :crumbs="[['label' => $page->title]]">
        @if ($isLegal)
            <p class="mt-6 flex flex-wrap gap-x-5 gap-y-1 font-mono text-xs text-text-subtle">
                @if ($page->version_label)<span>{{ __('public.pages.legal.version', ['version' => $page->version_label]) }}</span>@endif
                @if ($page->effective_at)<span>{{ __('public.pages.legal.effective', ['date' => $page->effective_at->locale('tr')->translatedFormat('d F Y')]) }}</span>@endif
            </p>
        @endif
    </x-ui.page-header>
    <div class="mx-auto max-w-[1240px] px-4 py-14 sm:px-8">
        <x-ui.prose :html="$html" />
        <p class="mt-14 border-t border-border pt-5 text-sm text-text-subtle">
            {{ __('public.last_updated') }}: {{ $page->updated_at?->locale('tr')->translatedFormat('d F Y') }}
        </p>
    </div>
</x-layouts.public>
