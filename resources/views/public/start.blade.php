{{-- /start — Akademi onboarding dersinden adım adım rehber (docs/SRS.md FR-PUB-009, US-PUB-05). --}}
<x-layouts.public :title="__('public.pages.start.eyebrow')" :description="__('public.pages.start.intro')">
    <x-ui.page-header :eyebrow="__('public.pages.start.eyebrow')" :title="__('public.pages.start.title')" :intro="__('public.pages.start.intro')"
                      :crumbs="[['label' => __('public.pages.start.eyebrow')]]" />

    <div class="mx-auto grid max-w-[1240px] gap-12 px-4 py-14 sm:px-8 lg:grid-cols-[16rem_1fr]">
        @if ($steps->isEmpty())
            <x-ui.empty-state :title="__('public.pages.start.empty')" icon="route" class="lg:col-span-2" />
        @else
            <nav aria-label="{{ __('public.pages.start.eyebrow') }}" class="hidden lg:block">
                <ol class="sticky top-24 space-y-1 border-l border-border">
                    @foreach ($steps as $step)
                        <li>
                            <a href="#adim-{{ $loop->iteration }}" class="-ml-px flex gap-3 border-l-2 border-transparent py-1.5 pl-4 text-sm text-text-muted hover:border-accent hover:text-text">
                                <span class="font-mono text-text-subtle">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                {{ $step['topic']->step_label ?: $step['topic']->title }}
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>

            <ol class="relative min-w-0 space-y-6">
                @foreach ($steps as $step)
                    <li id="adim-{{ $loop->iteration }}" class="relative scroll-mt-24 glass rounded-[var(--radius-xl)] p-6 sm:p-8">
                        <div class="flex items-center gap-4">
                            <span class="grid size-11 shrink-0 place-items-center rounded-full border-2 font-mono text-sm font-semibold {{ $loop->first ? 'border-accent bg-accent text-accent-fg' : 'border-border-input text-text' }}">
                                {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <div>
                                <p class="eyebrow">{{ __('public.pages.start.step', ['number' => $loop->iteration]) }}</p>
                                <h2 class="mt-1 font-display text-h3 font-bold font-wide text-text">{{ $step['topic']->title }}</h2>
                            </div>
                        </div>
                        <x-ui.prose :html="$step['html']" class="mt-5" />
                    </li>
                @endforeach

                <li id="katil" class="scroll-mt-24 rounded-lg border border-accent/40 bg-accent-soft p-6 sm:p-8">
                    <h2 class="font-display text-h3 font-bold font-wide text-text">{{ __('public.join.title') }}</h2>
                    <p class="mt-2 text-text-muted">{{ __('public.join.intro') }}</p>
                    @if ($status)
                        <x-brand.address-plate :status="$status" class="mt-5 bg-surface-1" />
                        <x-ui.button :href="$status->uri()" icon="gamepad-2" class="mt-4">{{ __('public.join.open_mta') }}</x-ui.button>
                    @endif
                </li>
            </ol>
        @endif
        <p class="flex gap-2 text-sm text-text-subtle lg:col-start-2">
            <x-lucide-shield-check class="mt-0.5 size-4 shrink-0 text-accent" aria-hidden="true" />{{ __('public.pages.start.official_only') }}
        </p>
    </div>
</x-layouts.public>
