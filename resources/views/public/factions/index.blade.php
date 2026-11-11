<x-layouts.public :title="__('public.pages.factions.eyebrow')" :description="__('public.pages.factions.intro')">
    <x-ui.page-header :eyebrow="__('public.pages.factions.eyebrow')" :title="__('public.pages.factions.title')" :intro="__('public.pages.factions.intro')"
                      :crumbs="[['label' => __('public.pages.factions.eyebrow')]]">
        <div class="mt-8">
            @include('public.partials.filter-chips', ['options' => __('public.pages.factions.types'), 'active' => $activeType, 'route' => 'public.factions.index', 'param' => 'type'])
        </div>
    </x-ui.page-header>

    <div class="mx-auto max-w-[1240px] px-4 py-14 sm:px-8">
        @if ($factions->isEmpty())
            <x-ui.empty-state :title="__('public.pages.factions.empty')" icon="shield" />
        @else
            <ul class="grid gap-4 md:grid-cols-2">
                @foreach ($factions as $faction)
                    @php($illegal = $faction->type === 'illegal')
                    <li class="group relative isolate overflow-hidden rounded-lg border border-border bg-surface-1 p-6 transition-colors hover:border-border-strong sm:p-7">
                        <span aria-hidden="true" class="absolute inset-x-0 top-0 h-px {{ $illegal ? 'bg-amber/50' : 'bg-accent/50' }}"></span>
                        <div class="flex items-start gap-4">
                            <span class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-md border border-border bg-surface-2">
                                @if ($faction->logo)
                                    <img src="{{ $faction->logo->url(480) }}" alt="" class="size-full object-cover" loading="lazy">
                                @else
                                    <x-lucide-shield class="size-5 {{ $illegal ? 'text-amber' : 'text-accent' }}" aria-hidden="true" />
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge :tone="$illegal ? 'amber' : 'accent'">{{ __('public.pages.factions.types.'.$faction->type) }}</x-ui.badge>
                                    <x-ui.badge :icon="$faction->applications_open ? 'door-open' : 'door-closed'">
                                        {{ $faction->applications_open ? __('public.pages.factions.applications_open') : __('public.pages.factions.applications_closed') }}
                                    </x-ui.badge>
                                </div>
                                <h2 class="mt-3 font-display text-lg font-bold font-wide text-text">
                                    <a href="{{ route('public.factions.show', $faction->slug) }}" class="after:absolute after:inset-0">{{ $faction->name }}</a>
                                </h2>
                                <p class="mt-2 text-sm text-text-muted">{{ $faction->summary }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.public>
