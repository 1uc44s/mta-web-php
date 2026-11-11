<x-layouts.public :title="__('public.pages.events.eyebrow')" :description="__('public.pages.events.intro')">
    <x-ui.page-header :eyebrow="__('public.pages.events.eyebrow')" :title="__('public.pages.events.title')" :intro="__('public.pages.events.intro')"
                      :crumbs="[['label' => __('public.pages.events.eyebrow')]]" />
    <div class="mx-auto max-w-[1240px] space-y-14 px-4 py-14 sm:px-8">
        <section aria-labelledby="upcoming-title">
            <h2 id="upcoming-title" class="eyebrow">{{ __('public.pages.events.upcoming') }}</h2>
            @if ($upcoming->isEmpty())
                <x-ui.empty-state :title="__('public.pages.events.empty')" icon="calendar-x" class="mt-4" />
            @else
                <ul class="mt-4 divide-y divide-border rounded-lg border border-border bg-surface-1">
                    @foreach ($upcoming as $event)@include('public.events.partials.row', ['event' => $event])@endforeach
                </ul>
            @endif
        </section>
        @if ($past->isNotEmpty())
            <section aria-labelledby="past-title">
                <h2 id="past-title" class="eyebrow">{{ __('public.pages.events.past') }}</h2>
                <ul class="mt-4 divide-y divide-border rounded-lg border border-border bg-surface-1 opacity-80">
                    @foreach ($past as $event)@include('public.events.partials.row', ['event' => $event])@endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.public>
