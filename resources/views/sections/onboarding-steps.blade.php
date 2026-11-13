{{-- CMS §4.2 onboarding_steps (v2): rota adımları cam panelde. steps: [title, text]. --}}
<section class="mx-auto max-w-[1240px] px-4 py-[clamp(5rem,3rem+5vw,9rem)] sm:px-8">
    <div class="glass relative isolate overflow-hidden rounded-[var(--radius-xl)] px-6 py-12 sm:px-12">
        <x-ui.ghost-word class="top-2 right-4 text-[clamp(4rem,12vw,10rem)]">{{ __('public.home_sections.start_ghost') }}</x-ui.ghost-word>
        <div class="flex flex-wrap items-end justify-between gap-6">
            <x-ui.section-header :eyebrow="$eyebrow ?? 'Rota'" :title="$title" :intro="$intro ?? null" />
            <x-ui.button variant="secondary" href="{{ route('public.start') }}" icon-right="arrow-right">{{ __('public.join.guide') }}</x-ui.button>
        </div>
        <x-brand.route-steps :steps="$steps ?? []" class="mt-14" />
    </div>
</section>