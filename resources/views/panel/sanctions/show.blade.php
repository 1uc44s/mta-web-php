{{-- Ceza detayı + itiraz — docs/SRS.md FR-MOD-004/005. --}}
<x-layouts.panel :title="__('moderation.types.'.$sanction->type)">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-ui.breadcrumb :items="[['label' => __('moderation.title'), 'href' => route('panel.sanctions.index')], ['label' => __('moderation.types.'.$sanction->type)]]" />
        <header class="flex flex-wrap items-center gap-3">
            <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ __('moderation.types.'.$sanction->type) }}</h1>
            <x-ui.badge :tone="$sanction->status === 'active' ? 'danger' : 'neutral'">{{ __('moderation.status.'.$sanction->status) }}</x-ui.badge>
        </header>

        <dl class="grid gap-4 rounded-lg border border-border bg-surface-1 p-6 sm:grid-cols-2">
            <div class="sm:col-span-2"><dt class="text-sm text-text-subtle">{{ __('moderation.reason') }}</dt><dd class="whitespace-pre-line text-text">{{ $sanction->public_reason }}</dd></div>
            <div><dt class="text-sm text-text-subtle">{{ __('moderation.starts') }}</dt><dd class="text-text">{{ $sanction->starts_at->format('d.m.Y H:i') }}</dd></div>
            <div><dt class="text-sm text-text-subtle">{{ __('moderation.ends') }}</dt><dd class="text-text">
                {{ $sanction->ends_at?->format('d.m.Y H:i') ?? (in_array($sanction->type, ['warning', 'kick'], true) ? __('moderation.one_off') : __('moderation.permanent')) }}
            </dd></div>
            <div><dt class="text-sm text-text-subtle">{{ __('moderation.issuer_label') }}</dt><dd class="text-text">{{ __('moderation.issuer') }}</dd></div>
        </dl>

        <section aria-labelledby="h-appeal" class="rounded-lg border border-border bg-surface-1 p-6">
            <h2 id="h-appeal" class="font-display text-h4 font-semibold text-text">{{ __('moderation.appeal') }}</h2>
            @if ($appeal)
                <p class="mt-2 text-sm"><span class="text-text-subtle">{{ __('moderation.appeal_status') }}:</span> <x-ui.badge>{{ __('moderation.appeal_statuses.'.$appeal->status) }}</x-ui.badge></p>
                <p class="mt-3 whitespace-pre-line text-sm text-text-muted">{{ $appeal->body }}</p>
                @if ($appeal->decision_note_public)
                    <p class="mt-3 text-sm"><span class="text-text-subtle">{{ __('moderation.appeal_note') }}:</span> <span class="whitespace-pre-line text-text">{{ $appeal->decision_note_public }}</span></p>
                @endif
            @elseif ($sanction->canBeAppealed())
                <form method="POST" action="{{ route('panel.sanctions.appeal', $sanction) }}" class="mt-3 space-y-3">
                    @csrf
                    <x-ui.field name="body" :label="__('moderation.appeal_body')" required><x-ui.input name="body" multiline rows="5" required minlength="30" maxlength="5000" /></x-ui.field>
                    <x-ui.button type="submit" size="sm" variant="secondary" icon="send">{{ __('moderation.appeal') }}</x-ui.button>
                </form>
            @else
                <p class="mt-2 text-sm text-text-muted">{{ __('moderation.appeal_closed') }}</p>
            @endif
        </section>
    </div>
</x-layouts.panel>
