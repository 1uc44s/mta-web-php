{{-- CMS §4.2 faq_list: show_on_home SSS'leri. Native <details> akordeon (JS gerektirmez). --}}
<section class="mx-auto max-w-[1240px] px-4 py-[clamp(4rem,3rem+4vw,7rem)] sm:px-8">
    <div class="grid gap-10 lg:grid-cols-[1fr_1.4fr]">
        <div>
            <x-ui.section-header :number="$number" :eyebrow="$eyebrow ?? 'SSS'" :title="$title ?? 'Aklındaki sorular'" :intro="$intro ?? null" />
            <x-ui.button variant="ghost" href="{{ route('public.faq') }}" icon-right="arrow-right" class="mt-6 -ml-4">{{ __('public.home_sections.all_faq') }}</x-ui.button>
        </div>
        @include('public.partials.faq-items', ['faqs' => $faqs, 'answers' => $answers])
    </div>
</section>
