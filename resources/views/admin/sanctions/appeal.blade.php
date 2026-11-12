{{-- İtiraz kararı — docs/SRS.md FR-MOD-005 (cezayı veren karar veremez). --}}
<x-layouts.admin :title="__('moderation.admin.appeals')">
    <x-admin.header :title="__('moderation.admin.appeals')" :description="$appeal->user->username.' · '.__('moderation.types.'.$appeal->sanction->type)" :back="route('admin.appeals.index')" :back-label="__('moderation.admin.appeals')" />

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <x-admin.card>
            <p class="text-sm text-text-subtle">{{ __('moderation.admin.public_reason') }}</p>
            <p class="whitespace-pre-line text-text">{{ $appeal->sanction->public_reason }}</p>
            <p class="mt-4 text-sm text-text-subtle">{{ __('moderation.appeal') }}</p>
            <p class="whitespace-pre-line text-text">{{ $appeal->body }}</p>
            <p class="mt-4 text-sm"><a class="text-accent underline" href="{{ route('admin.sanctions.show', $appeal->sanction) }}">{{ __('moderation.admin.sanctions') }} →</a>
                · {{ __('moderation.admin.issued_by') }}: {{ $appeal->sanction->issuer?->username }}</p>
        </x-admin.card>

        <x-admin.card :title="__('moderation.admin.decide')">
            @if (! in_array($appeal->status, ['submitted', 'in_review'], true))
                <p class="text-sm"><x-ui.badge>{{ __('moderation.appeal_statuses.'.$appeal->status) }}</x-ui.badge></p>
                <p class="mt-2 text-sm text-text">{{ $appeal->decision_note_public }}</p>
            @elseif ($isIssuer)
                <p class="text-sm text-amber">{{ __('moderation.errors.own_sanction') }}</p>
            @else
                <form method="POST" action="{{ route('admin.appeals.decide', $appeal) }}" class="space-y-3">
                    @csrf
                    <x-ui.field name="note_public" :label="__('moderation.admin.decision_public')" required><x-ui.input name="note_public" multiline rows="3" required /></x-ui.field>
                    <x-ui.field name="note_internal" :label="__('moderation.admin.internal_note')"><x-ui.input name="note_internal" multiline rows="2" /></x-ui.field>
                    @error('decision')<p role="alert" class="text-sm text-danger">{{ $message }}</p>@enderror
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button type="submit" size="sm" name="decision" value="accepted">{{ __('moderation.admin.accept') }}</x-ui.button>
                        <x-ui.button type="submit" size="sm" variant="secondary" name="decision" value="rejected">{{ __('moderation.admin.reject') }}</x-ui.button>
                    </div>
                </form>
            @endif
        </x-admin.card>
    </div>
</x-layouts.admin>
