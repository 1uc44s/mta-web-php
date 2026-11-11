{{-- CMS §4.2 onboarding_steps: rota adımları. steps: [title, text]. --}}
<section class="border-y border-border bg-surface-1">
    <div class="mx-auto max-w-[1240px] px-4 py-[clamp(4rem,3rem+4vw,7rem)] sm:px-8">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <x-ui.section-header :number="$number" :eyebrow="$eyebrow ?? 'Rota'" :title="$title" :intro="$intro ?? null" />
            <x-ui.button variant="secondary" href="{{ route('public.start') }}" icon-right="arrow-right">{{ __('public.join.guide') }}</x-ui.button>
        </div>
        <x-brand.route-steps :steps="$steps ?? []" class="mt-14" />
    </div>
</section>
