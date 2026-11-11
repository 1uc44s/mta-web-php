<x-layouts.public :title="__('public.pages.systems.eyebrow')" :description="__('public.pages.systems.intro')">
    <x-ui.page-header :eyebrow="__('public.pages.systems.eyebrow')" :title="__('public.pages.systems.title')" :intro="__('public.pages.systems.intro')"
                      :crumbs="[['label' => __('public.pages.systems.eyebrow')]]" />

    <div class="mx-auto max-w-[1240px] px-4 py-14 sm:px-8">
        @forelse ($categories as $category)
            <section class="{{ $loop->first ? '' : 'mt-16' }}" aria-labelledby="cat-{{ $category->slug }}">
                <div class="flex items-center gap-3">
                    <span class="grid size-9 place-items-center rounded-md bg-accent-soft text-accent">
                        <x-dynamic-component :component="'lucide-'.($category->icon ?: 'layers')" class="size-4" aria-hidden="true" />
                    </span>
                    <h2 id="cat-{{ $category->slug }}" class="font-display text-h3 font-bold font-wide text-text">{{ $category->name }}</h2>
                    <span class="font-mono text-xs text-text-subtle">{{ $category->systems->count() }}</span>
                </div>
                <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($category->systems as $system)
                        <li class="group relative rounded-lg border border-border bg-surface-1 p-6 transition-colors hover:border-border-strong hover:bg-surface-2">
                            <h3 class="font-medium text-text"><a href="{{ route('public.systems.show', $system->slug) }}" class="after:absolute after:inset-0">{{ $system->name }}</a></h3>
                            <p class="mt-2 text-sm text-text-muted">{{ $system->summary }}</p>
                            <x-lucide-arrow-up-right class="absolute top-6 right-6 size-4 text-text-subtle group-hover:text-accent" aria-hidden="true" />
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <x-ui.empty-state :title="__('public.pages.systems.empty')" icon="layers" />
        @endforelse
    </div>
</x-layouts.public>
