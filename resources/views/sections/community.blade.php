{{-- CMS §4.2 community: Discord daveti (ayarlardan) + sosyal bağlantılar. --}}
<section class="mx-auto max-w-[1240px] px-4 py-[clamp(4rem,3rem+4vw,7rem)] sm:px-8">
    <div class="relative isolate overflow-hidden rounded-lg border border-border bg-surface-1 px-6 py-12 sm:px-12">
        <x-brand.meridian-globe class="pointer-events-none absolute top-1/2 -right-56 -z-10 size-[36rem] -translate-y-1/2 text-grid-line" />
        <div class="max-w-xl">
            <x-brand.eyebrow :number="$number">{{ $eyebrow ?? 'Topluluk' }}</x-brand.eyebrow>
            <h2 class="mt-4 font-display text-h2 font-bold font-wide text-text">{{ $title }}</h2>
            @if (! empty($intro))<p class="mt-4 text-lg text-text-muted">{{ $intro }}</p>@endif
            <div class="mt-8 flex flex-wrap gap-3">
                @if ($discord)
                    <x-ui.button :href="\App\Support\Url::safe($discord)" target="_blank" rel="noopener" icon="message-circle">{{ __('public.home_sections.discord_join') }}</x-ui.button>
                @endif
                @foreach ($socials as $link)
                    <x-ui.button variant="secondary" :href="\App\Support\Url::safe($link->url)" target="_blank" rel="noopener">{{ $link->label }}</x-ui.button>
                @endforeach
            </div>
        </div>
    </div>
</section>
