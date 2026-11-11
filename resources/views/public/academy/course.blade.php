<x-layouts.public :title="$course->seo_title ?: $course->title" :description="$course->meta_description ?: $course->summary">
    <x-ui.page-header :eyebrow="__('public.pages.academy.levels.'.$course->level)" :title="$course->title" :intro="$course->summary"
                      :crumbs="[['label' => __('public.pages.academy.eyebrow'), 'href' => route('public.academy.index')], ['label' => $course->title]]">
        @if ($course->topics->isNotEmpty())
            <x-ui.button :href="route('public.academy.topic', [$course->slug, $course->topics->first()->slug])" icon-right="arrow-right" class="mt-8">
                {{ __('public.pages.academy.start_course') }}
            </x-ui.button>
        @endif
    </x-ui.page-header>
    <div class="mx-auto max-w-[1240px] px-4 py-14 sm:px-8">
        <ol class="divide-y divide-border rounded-lg border border-border bg-surface-1">
            @foreach ($course->topics as $topic)
                <li class="group relative flex items-center gap-5 px-5 py-4 hover:bg-surface-2">
                    <span class="font-mono text-sm text-text-subtle">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <a href="{{ route('public.academy.topic', [$course->slug, $topic->slug]) }}" class="flex-1 font-medium text-text after:absolute after:inset-0">{{ $topic->title }}</a>
                    <span class="font-mono text-xs text-text-subtle">~{{ __('public.minutes', ['count' => $topic->estimated_minutes]) }}</span>
                    <x-lucide-chevron-right class="size-4 text-text-subtle group-hover:text-accent" aria-hidden="true" />
                </li>
            @endforeach
        </ol>
    </div>
</x-layouts.public>
