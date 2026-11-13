{{-- CMS §4.2 events_upcoming (otomatik; boşsa render edilmez). --}}
<section class="mx-auto max-w-[1240px] px-4 py-[clamp(4rem,3rem+4vw,7rem)] sm:px-8">
    <div class="flex flex-wrap items-end justify-between gap-6">
        <x-ui.section-header :number="$number" :eyebrow="$eyebrow ?? 'Etkinlikler'" :title="$title ?? 'Takvimde neler var?'" />
        <x-ui.button variant="ghost" href="{{ route('public.events.index') }}" icon-right="arrow-right">{{ __('public.home_sections.all_events') }}</x-ui.button>
    </div>
    <ul class="mt-10 divide-y divide-border glass rounded-[var(--radius-xl)]">
        @foreach ($events as $event)
            @include('public.events.partials.row', ['event' => $event])
        @endforeach
    </ul>
</section>
