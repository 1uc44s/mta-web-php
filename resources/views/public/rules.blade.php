{{-- /rules — kategori → kural, #kod derin bağlantı, istemci tarafı arama (docs/SRS.md FR-PUB-010). --}}
<x-layouts.public :title="__('public.pages.rules.eyebrow')" :description="__('public.pages.rules.intro')">
    <x-ui.page-header :eyebrow="__('public.pages.rules.eyebrow')" :title="__('public.pages.rules.title')" :intro="__('public.pages.rules.intro')"
                      :crumbs="[['label' => __('public.pages.rules.eyebrow')]]">
        <p class="mt-6 flex flex-wrap gap-x-5 gap-y-1 font-mono text-xs text-text-subtle">
            <span>{{ __('public.pages.rules.version') }}: {{ $version }}</span>
            @if ($lastUpdated)<span>{{ __('public.last_updated') }}: {{ $lastUpdated->locale('tr')->translatedFormat('d F Y') }}</span>@endif
        </p>
    </x-ui.page-header>

    <div class="mx-auto grid max-w-[1240px] gap-12 px-4 py-14 sm:px-8 lg:grid-cols-[16rem_1fr]">
        @if ($categories->isEmpty())
            <x-ui.empty-state :title="__('public.pages.rules.empty')" icon="scale" class="lg:col-span-2" />
        @else
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <label for="rules-search" class="text-sm font-medium text-text">{{ __('public.pages.rules.search') }}</label>
                <div class="relative mt-2">
                    <x-lucide-search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-text-subtle" aria-hidden="true" />
                    <input id="rules-search" type="search" data-rules-filter placeholder="{{ __('public.pages.rules.search_placeholder') }}"
                           class="min-h-11 w-full rounded-sm border border-border-input bg-surface-2 pr-3 pl-9 text-sm text-text placeholder:text-text-subtle focus-visible:border-accent">
                </div>
                <nav aria-label="{{ __('public.pages.rules.contents') }}" class="mt-6 hidden lg:block">
                    <h2 class="eyebrow">{{ __('public.pages.rules.contents') }}</h2>
                    <ul class="mt-3 space-y-1 border-l border-border">
                        @foreach ($categories as $category)
                            <li><a href="#kategori-{{ $category->slug }}" class="-ml-px block border-l-2 border-transparent py-1 pl-4 text-sm text-text-muted hover:border-accent hover:text-text">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            </aside>

            <div class="min-w-0">
                <p data-rules-empty hidden class="rounded-md border border-dashed border-border-strong px-4 py-6 text-center text-sm text-text-muted">{{ __('public.pages.rules.no_results') }}</p>
                @foreach ($categories as $category)
                    <section id="kategori-{{ $category->slug }}" data-rule-group class="scroll-mt-24 {{ $loop->first ? '' : 'mt-14' }}">
                        <div class="flex items-baseline gap-3">
                            <span class="font-mono text-sm text-accent">{{ $category->prefix }}</span>
                            <h2 class="font-display text-h3 font-bold font-wide text-text">{{ $category->name }}</h2>
                        </div>
                        @if ($category->description)<p class="mt-2 text-text-muted">{{ $category->description }}</p>@endif
                        <ol class="mt-6 space-y-3">
                            @foreach ($category->rules as $rule)
                                <li id="{{ $rule->anchor() }}" data-rule-item data-search="{{ mb_strtolower($rule->code.' '.$rule->title.' '.strip_tags($rendered[$rule->id]->toHtml()), 'UTF-8') }}"
                                    class="rule-target scroll-mt-24 rounded-md border border-border bg-surface-1 p-5 transition-colors">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <h3 class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                            <a href="#{{ $rule->anchor() }}" class="font-mono text-sm text-accent hover:underline">{{ $rule->code }}</a>
                                            <span class="font-medium text-text">{{ $rule->title }}</span>
                                        </h3>
                                        <button type="button" data-copy="{{ route('public.rules') }}#{{ $rule->anchor() }}" data-copied-label="{{ __('public.join.copied') }}"
                                                class="inline-flex min-h-8 items-center gap-1.5 rounded-sm px-2 text-xs text-text-subtle hover:bg-surface-2 hover:text-text" aria-label="{{ __('public.pages.rules.copy_link') }}: {{ $rule->code }}">
                                            <x-lucide-link class="size-3.5" aria-hidden="true" /><span data-copy-label>{{ __('public.pages.rules.copy_link') }}</span>
                                        </button>
                                    </div>
                                    <x-ui.prose :html="$rendered[$rule->id]" class="mt-3 text-base" />
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.public>
