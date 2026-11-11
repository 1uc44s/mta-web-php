{{-- CMS §4.2 rich_text --}}
<section class="mx-auto max-w-[1240px] px-4 py-[clamp(3rem,2rem+4vw,6rem)] sm:px-8">
    @if (! empty($title))
        <x-ui.section-header :number="$number" :eyebrow="$eyebrow ?? null" :title="$title" />
    @endif
    <x-ui.prose :html="$html" class="mt-8" />
</section>
