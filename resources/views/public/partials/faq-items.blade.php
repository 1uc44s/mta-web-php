{{-- SSS akordeonu: native <details>/<summary> (klavye ve ekran okuyucu desteği yerleşik). --}}
<div class="divide-y divide-border border-y border-border">
    @foreach ($faqs as $faq)
        <details class="group py-1" @if ($loop->first) open @endif>
            <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-6 py-3 text-left font-medium text-text marker:hidden [&::-webkit-details-marker]:hidden">
                <span>{{ $faq->question }}</span>
                <span class="grid size-8 shrink-0 place-items-center rounded-full border border-border text-text-muted transition-transform duration-[var(--dur-base)] group-open:rotate-45 group-open:border-accent group-open:text-accent">
                    <x-lucide-plus class="size-4" aria-hidden="true" />
                </span>
            </summary>
            <x-ui.prose :html="$answers[$faq->id]" class="pb-5 text-base" />
        </details>
    @endforeach
</div>
