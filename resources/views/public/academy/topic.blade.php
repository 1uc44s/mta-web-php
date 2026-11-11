<x-layouts.public :title="$topic->seo_title ?: $topic->title" :description="$topic->meta_description">
    <x-ui.page-header :eyebrow="__('public.pages.academy.topic_of', ['current' => $position, 'total' => $course->topics->count()])" :title="$topic->title"
                      :crumbs="[['label' => __('public.pages.academy.eyebrow'), 'href' => route('public.academy.index')], ['label' => $course->title, 'href' => route('public.academy.course', $course->slug)], ['label' => $topic->title]]">
        <progress value="{{ $position }}" max="{{ $course->topics->count() }}" aria-label="{{ __('public.pages.academy.topic_of', ['current' => $position, 'total' => $course->topics->count()]) }}"
                  class="mt-8 block h-1 w-full max-w-md appearance-none overflow-hidden rounded-pill bg-surface-3 [&::-moz-progress-bar]:bg-accent [&::-webkit-progress-bar]:bg-surface-3 [&::-webkit-progress-value]:rounded-pill [&::-webkit-progress-value]:bg-accent"></progress>
    </x-ui.page-header>
    <div class="mx-auto grid max-w-[1240px] gap-12 px-4 py-14 sm:px-8 lg:grid-cols-[1fr_18rem]">
        <article class="min-w-0">
            <x-ui.prose :html="$html" />
            @auth
                {{-- docs/CMS.md §5.2: "Okudum" (idempotent) --}}
                <form method="POST" action="{{ route('panel.academy.complete', $topic->id) }}" class="mt-10">@csrf
                    <x-ui.button type="submit" variant="secondary" icon="check">Okudum, tamamlandı olarak işaretle</x-ui.button>
                </form>
            @endauth
            <nav aria-label="Konular arası gezinme" class="mt-14 grid gap-3 border-t border-border pt-6 sm:grid-cols-2">
                @if ($previous)
                    <a href="{{ route('public.academy.topic', [$course->slug, $previous->slug]) }}" class="group rounded-md border border-border p-4 hover:border-border-strong">
                        <span class="eyebrow">← {{ __('public.pages.academy.previous') }}</span>
                        <span class="mt-1 block font-medium text-text group-hover:text-accent">{{ $previous->title }}</span>
                    </a>
                @else<span></span>@endif
                @if ($next)
                    <a href="{{ route('public.academy.topic', [$course->slug, $next->slug]) }}" class="group rounded-md border border-border p-4 text-right hover:border-border-strong">
                        <span class="eyebrow">{{ __('public.pages.academy.next') }} →</span>
                        <span class="mt-1 block font-medium text-text group-hover:text-accent">{{ $next->title }}</span>
                    </a>
                @endif
            </nav>
        </article>
        @if ($topic->rules->isNotEmpty())
            <aside class="lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-lg border border-border bg-surface-1 p-5">
                    <h2 class="eyebrow">{{ __('public.pages.academy.related_rules') }}</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($topic->rules as $rule)
                            <li><a href="{{ route('public.rules') }}#{{ $rule->anchor() }}" class="flex gap-2 text-sm text-text-muted hover:text-text"><span class="font-mono text-accent">{{ $rule->code }}</span>{{ $rule->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        @endif
    </div>
</x-layouts.public>
