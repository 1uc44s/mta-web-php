@props(['status' => null])
{{--
    "Oyuna Katıl" paneli (docs/SRS.md FR-PUB-004). Native <dialog>: focus trap + Esc yerleşik (NFR-A11Y-004).
    Açan butonlar data-dialog-open="join-dialog" taşır; JS yoksa href="/start#katil" çalışır.
--}}
<dialog id="join-dialog" aria-labelledby="join-dialog-title"
        class="dialog-mrp m-auto w-[min(32rem,calc(100vw-2rem))] rounded-lg border border-border-strong bg-surface-1 p-0 text-text shadow-2">
    <div class="flex items-start justify-between gap-4 border-b border-border px-6 py-5">
        <div>
            <p class="eyebrow"><span class="text-accent">§</span> {{ config('app.name') }}</p>
            <h2 id="join-dialog-title" class="mt-2 font-display font-wide text-h3 font-bold">{{ __('public.join.title') }}</h2>
        </div>
        <form method="dialog">
            <button class="grid size-10 place-items-center rounded-md text-text-muted hover:bg-surface-2 hover:text-text" aria-label="{{ __('public.join.close') }}">
                <x-lucide-x class="size-5" aria-hidden="true" />
            </button>
        </form>
    </div>
    <div class="space-y-5 px-6 py-6">
        <p class="text-text-muted">{{ __('public.join.intro') }}</p>
        @if ($status)
            <x-brand.address-plate :status="$status" />
            <div class="flex flex-wrap gap-3">
                <x-ui.button :href="$status->uri()" icon="gamepad-2">{{ __('public.join.open_mta') }}</x-ui.button>
                <x-ui.button :href="route('public.start')" variant="secondary" icon="route">{{ __('public.join.guide') }}</x-ui.button>
            </div>
        @else
            <p class="rounded-sm border border-dashed border-border-strong px-4 py-3 text-sm text-text-muted">{{ __('public.status.no_server') }}</p>
        @endif
        <p class="flex gap-2 text-sm text-text-subtle">
            <x-lucide-info class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <span>{{ __('public.join.no_mta') }}</span>
        </p>
    </div>
</dialog>
