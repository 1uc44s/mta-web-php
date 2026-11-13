<x-layouts.public :title="$faction->seo_title ?: $faction->name" :description="$faction->meta_description ?: $faction->summary">
    <x-ui.page-header :eyebrow="__('public.pages.factions.types.'.$faction->type)" :title="$faction->name" :intro="$faction->summary"
                      :crumbs="[['label' => __('public.pages.factions.eyebrow'), 'href' => route('public.factions.index')], ['label' => $faction->name]]" />
    <div class="mx-auto grid max-w-[1240px] gap-12 px-4 py-14 sm:px-8 lg:grid-cols-[1fr_22rem]">
        <article class="min-w-0">
            <x-ui.prose :html="$description" />
        </article>
        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            <div class="glass rounded-[var(--radius-xl)] p-5">
                <x-ui.badge :tone="$faction->applications_open ? 'accent' : 'neutral'" :icon="$faction->applications_open ? 'door-open' : 'door-closed'">
                    {{ $faction->applications_open ? __('public.pages.factions.applications_open') : __('public.pages.factions.applications_closed') }}
                </x-ui.badge>
                @unless ($requirements->isEmpty())
                    <h2 class="eyebrow mt-5">{{ __('public.pages.factions.requirements') }}</h2>
                    <x-ui.prose :html="$requirements" class="mt-3 text-sm" />
                @endunless
                @if ($faction->applications_open)
                    <p class="mt-5 border-t border-border pt-4 text-sm text-text-muted">{{ __('public.pages.factions.apply_soon') }}</p>
                @endif
            </div>
            @if ($ruleCategoryPublished)
                <x-ui.button variant="secondary" :href="route('public.rules').'#kategori-'.$faction->ruleCategory->slug" icon="scale" class="w-full">{{ __('public.pages.factions.rules') }}</x-ui.button>
            @endif
        </aside>
    </div>
</x-layouts.public>
