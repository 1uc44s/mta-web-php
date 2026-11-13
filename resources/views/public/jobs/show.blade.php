<x-layouts.public :title="$job->seo_title ?: $job->name" :description="$job->meta_description ?: $job->summary">
    <x-ui.page-header :eyebrow="__('public.pages.jobs.classes.'.$job->class)" :title="$job->name" :intro="$job->summary"
                      :crumbs="[['label' => __('public.pages.jobs.eyebrow'), 'href' => route('public.jobs.index')], ['label' => $job->name]]">
        @unless ($job->is_active)
            <x-ui.badge icon="lock" class="mt-6">{{ __('public.pages.jobs.inactive') }}</x-ui.badge>
        @endunless
    </x-ui.page-header>
    <div class="mx-auto grid max-w-[1240px] gap-12 px-4 py-14 sm:px-8 lg:grid-cols-[1fr_20rem]">
        <article class="min-w-0 space-y-12">
            <x-ui.prose :html="$description" />
            @foreach (['duties' => $duties, 'progression' => $progression, 'earnings' => $earnings] as $key => $html)
                @unless ($html->isEmpty())
                    <section>
                        <h2 class="font-display text-h3 font-bold font-wide text-text">{{ __('public.pages.jobs.'.$key) }}</h2>
                        <x-ui.prose :html="$html" class="mt-4" />
                    </section>
                @endunless
            @endforeach
        </article>
        <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
            @if ($job->access_requirement)
                <div class="glass rounded-[var(--radius-xl)] p-5">
                    <h2 class="eyebrow">{{ __('public.pages.jobs.access') }}</h2>
                    <p class="mt-3 text-sm text-text-muted">{{ $job->access_requirement }}</p>
                </div>
            @endif
            <p class="font-mono text-xs text-text-subtle">{{ __('public.last_updated') }}: {{ $job->updated_at?->locale('tr')->translatedFormat('d F Y') }}</p>
        </aside>
    </div>
</x-layouts.public>
