<x-layouts.public :title="__('public.pages.academy.eyebrow')" :description="__('public.pages.academy.intro')">
    <x-ui.page-header :eyebrow="__('public.pages.academy.eyebrow')" :title="__('public.pages.academy.title')" :intro="__('public.pages.academy.intro')"
                      :crumbs="[['label' => __('public.pages.academy.eyebrow')]]" />
    <div class="mx-auto max-w-[1240px] px-4 py-14 sm:px-8">
        @if ($courses->isEmpty())
            <x-ui.empty-state :title="__('public.pages.academy.empty')" icon="graduation-cap" />
        @else
            <ol class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    <li class="group relative flex flex-col rounded-lg border border-border bg-surface-1 p-6 transition-colors hover:border-border-strong hover:bg-surface-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs text-text-subtle">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <div class="flex gap-2">
                                @if ($course->is_required)<x-ui.badge tone="amber" icon="star">{{ __('public.pages.academy.required') }}</x-ui.badge>@endif
                                <x-ui.badge>{{ __('public.pages.academy.levels.'.$course->level) }}</x-ui.badge>
                            </div>
                        </div>
                        <h2 class="mt-5 font-display text-lg font-bold font-wide text-text">
                            <a href="{{ route('public.academy.course', $course->slug) }}" class="after:absolute after:inset-0">{{ $course->title }}</a>
                        </h2>
                        <p class="mt-2 flex-1 text-sm text-text-muted">{{ $course->summary }}</p>
                        <p class="mt-5 flex gap-4 border-t border-border pt-3 font-mono text-xs text-text-subtle">
                            <span>{{ __('public.pages.academy.topics', ['count' => $course->topics_count]) }}</span>
                            <span>~{{ __('public.minutes', ['count' => $course->estimated_minutes]) }}</span>
                        </p>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</x-layouts.public>
