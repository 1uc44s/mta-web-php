@php
    $faqSchema = ['@type' => 'FAQPage', 'mainEntity' => $groups->flatten()->map(fn ($faq) => [
        '@type' => 'Question', 'name' => $faq->question,
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($answers[$faq->id]->toHtml())],
    ])->values()->all()];
@endphp
<x-layouts.public :title="__('public.pages.faq.title')" :description="__('public.pages.faq.intro')" :json-ld="$groups->isEmpty() ? [] : [$faqSchema]">
    <x-ui.page-header :eyebrow="__('public.pages.faq.eyebrow')" :title="__('public.pages.faq.title')" :intro="__('public.pages.faq.intro')"
                      :crumbs="[['label' => __('public.pages.faq.eyebrow')]]">
        <x-ui.button variant="secondary" :href="route('public.contact')" icon="mail" class="mt-8">{{ __('public.contact.title') }}</x-ui.button>
    </x-ui.page-header>
    <div class="mx-auto max-w-[1240px] space-y-14 px-4 py-14 sm:px-8">
        @forelse ($groups as $group => $faqs)
            <section aria-labelledby="faq-{{ $group }}" class="grid gap-6 lg:grid-cols-[16rem_1fr]">
                <h2 id="faq-{{ $group }}" class="eyebrow pt-4">{{ __('public.pages.faq.groups.'.$group) }}</h2>
                @include('public.partials.faq-items', ['faqs' => $faqs, 'answers' => $answers])
            </section>
        @empty
            <x-ui.empty-state :title="__('public.pages.faq.empty')" icon="circle-help" />
        @endforelse
    </div>
</x-layouts.public>
