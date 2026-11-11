<x-layouts.public :title="$system->seo_title ?: $system->name" :description="$system->meta_description ?: $system->summary">
    <x-ui.page-header :eyebrow="$system->category->name" :title="$system->name" :intro="$system->summary"
                      :crumbs="[['label' => __('public.pages.systems.eyebrow'), 'href' => route('public.systems.index')], ['label' => $system->name]]" />

    <div class="mx-auto grid max-w-[1240px] gap-12 px-4 py-14 sm:px-8 lg:grid-cols-[1fr_20rem]">
        <article class="min-w-0 space-y-12">
            <section>
                <h2 class="font-display text-h3 font-bold font-wide text-text">{{ __('public.pages.systems.how_it_works') }}</h2>
                <x-ui.prose :html="$howItWorks" class="mt-4" />
            </section>
            @unless ($howToAccess->isEmpty())
                <section>
                    <h2 class="font-display text-h3 font-bold font-wide text-text">{{ __('public.pages.systems.how_to_access') }}</h2>
                    <x-ui.prose :html="$howToAccess" class="mt-4" />
                </section>
            @endunless
        </article>
        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            @unless ($requirements->isEmpty())
                <div class="rounded-lg border border-border bg-surface-1 p-5">
                    <h2 class="eyebrow">{{ __('public.pages.systems.requirements') }}</h2>
                    <x-ui.prose :html="$requirements" class="mt-3 text-sm" />
                </div>
            @endunless
            <p class="font-mono text-xs text-text-subtle">
                {{ __('public.last_updated') }}: {{ ($system->last_reviewed_at ?? $system->updated_at)?->locale('tr')->translatedFormat('d F Y') }}
            </p>
        </aside>
    </div>
</x-layouts.public>
