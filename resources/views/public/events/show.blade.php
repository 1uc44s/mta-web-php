@php
    $tz = config('roleplay.display_timezone');
    $schema = ['@type' => 'Event', 'name' => $event->title, 'startDate' => $event->starts_at->toIso8601String(),
        'endDate' => $event->ends_at?->toIso8601String(), 'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
        'location' => ['@type' => 'VirtualLocation', 'url' => route('public.events.show', $event->slug)], 'description' => $event->summary];
@endphp
<x-layouts.public :title="$event->seo_title ?: $event->title" :description="$event->meta_description ?: $event->summary" :json-ld="[$schema]">
    <x-ui.page-header :eyebrow="$event->starts_at->timezone($tz)->locale('tr')->translatedFormat('d F Y · H:i')" :title="$event->title" :intro="$event->summary"
                      :crumbs="[['label' => __('public.pages.events.eyebrow'), 'href' => route('public.events.index')], ['label' => $event->title]]">
        @if ($event->location_label)
            <p class="mt-6 flex items-center gap-2 text-sm text-text-muted"><x-lucide-map-pin class="size-4 text-accent" aria-hidden="true" />{{ $event->location_label }}</p>
        @endif
    </x-ui.page-header>
    <div class="mx-auto max-w-[1240px] px-4 py-14 sm:px-8">
        <x-ui.prose :html="$html" />
    </div>
</x-layouts.public>
