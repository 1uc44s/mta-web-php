<x-layouts.public :title="__('public.pages.jobs.eyebrow')" :description="__('public.pages.jobs.intro')">
    <x-ui.page-header :eyebrow="__('public.pages.jobs.eyebrow')" :title="__('public.pages.jobs.title')" :intro="__('public.pages.jobs.intro')"
                      :crumbs="[['label' => __('public.pages.jobs.eyebrow')]]">
        <div class="mt-8">
            @include('public.partials.filter-chips', ['options' => __('public.pages.jobs.classes'), 'active' => $activeClass, 'route' => 'public.jobs.index', 'param' => 'class'])
        </div>
    </x-ui.page-header>

    <div class="mx-auto max-w-[1240px] px-4 py-14 sm:px-8">
        @if ($jobs->isEmpty())
            <x-ui.empty-state :title="__('public.pages.jobs.empty')" icon="briefcase" />
        @else
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($jobs as $job)
                    <li class="group relative flex flex-col glass rounded-[var(--radius-xl)] p-6 transition-colors hover:border-border-strong hover:bg-surface-2 {{ $job->is_active ? '' : 'opacity-70' }}">
                        <div class="flex items-center justify-between gap-3">
                            <x-ui.badge :tone="$job->class === 'legal' ? 'accent' : ($job->class === 'event' ? 'amber' : 'neutral')">{{ __('public.pages.jobs.classes.'.$job->class) }}</x-ui.badge>
                            @unless ($job->is_active)<x-ui.badge icon="lock">{{ __('public.pages.jobs.inactive') }}</x-ui.badge>@endunless
                        </div>
                        <h2 class="mt-5 font-medium text-text"><a href="{{ route('public.jobs.show', $job->slug) }}" class="after:absolute after:inset-0">{{ $job->name }}</a></h2>
                        <p class="mt-2 flex-1 text-sm text-text-muted">{{ $job->summary }}</p>
                        @if ($job->access_requirement)
                            <p class="mt-4 border-t border-border pt-3 text-xs text-text-subtle">{{ __('public.pages.jobs.access') }}: {{ $job->access_requirement }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.public>
