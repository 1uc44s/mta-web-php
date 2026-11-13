{{-- /contact — docs/SRS.md FR-PUB-013 (honeypot + min. süre + throttle). --}}
<x-layouts.public :title="__('public.contact.title')" :description="__('public.contact.intro')">
    <x-ui.page-header :eyebrow="__('public.contact.eyebrow')" :title="__('public.contact.title')" :intro="__('public.contact.intro')"
                      :crumbs="[['label' => __('public.contact.eyebrow')]]" />
    <div class="mx-auto grid max-w-[1240px] gap-12 px-4 py-14 sm:px-8 lg:grid-cols-[1fr_20rem]">
        <form method="POST" action="{{ route('public.contact.store') }}" class="space-y-5 glass rounded-[var(--radius-xl)] p-6 sm:p-8" novalidate>
            @csrf
            <input type="hidden" name="form_token" value="{{ $formToken }}">
            {{-- Honeypot: insanlar görmez, botlar doldurur --}}
            <div class="hidden" aria-hidden="true">
                <label for="f-website">Web sitesi</label>
                <input id="f-website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            @if ($errors->any())
                <div role="alert" class="rounded-sm border border-danger/40 px-4 py-3 text-sm text-danger">{{ __('errors.api.VALIDATION_ERROR') }}</div>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.field name="name" :label="__('public.contact.attributes.name')" required>
                    <x-ui.input name="name" autocomplete="name" required maxlength="100" />
                </x-ui.field>
                <x-ui.field name="email" :label="__('public.contact.attributes.email')" required>
                    <x-ui.input name="email" type="email" autocomplete="email" required maxlength="254" />
                </x-ui.field>
            </div>
            <x-ui.field name="subject" :label="__('public.contact.attributes.subject')" required>
                <x-ui.input name="subject" required maxlength="150" />
            </x-ui.field>
            <x-ui.field name="body" :label="__('public.contact.attributes.body')" :hint="__('public.contact.hints.body')" required>
                <x-ui.input name="body" multiline :rows="7" hint required minlength="20" maxlength="5000" />
            </x-ui.field>
            <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                <p class="text-xs text-text-subtle">{{ __('public.contact.privacy') }}</p>
                <x-ui.button type="submit" icon="send" data-submit-once>{{ __('public.contact.send') }}</x-ui.button>
            </div>
        </form>
        <aside class="space-y-4">
            <div class="glass rounded-[var(--radius-xl)] p-5">
                <h2 class="eyebrow">{{ __('public.contact.response_time') }}</h2>
                <p class="mt-2 text-sm text-text-muted">{{ $site['support.expected_response_text'] ?? '' }}</p>
            </div>
            @if (! empty($site['social.discord_invite']))
                <x-ui.button variant="secondary" :href="\App\Support\Url::safe($site['social.discord_invite'])" target="_blank" rel="noopener" icon="message-circle" class="w-full">{{ __('public.contact.discord') }}</x-ui.button>
            @endif
        </aside>
    </div>
</x-layouts.public>
