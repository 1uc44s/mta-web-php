{{-- Başvuru detayı (oyuncu) — iç mesaj ve iç not yüklenmez (FR-APP-006). --}}
<x-layouts.panel :title="$application->type->name">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-ui.breadcrumb :items="[['label' => __('applications.title'), 'href' => route('panel.applications.index')], ['label' => $application->type->name]]" />
        <header class="flex flex-wrap items-center gap-3">
            <h1 class="font-display text-h2 font-bold font-expanded text-text">{{ $application->type->name }}</h1>
            <x-ui.badge :tone="match ($application->status) { 'approved' => 'success', 'rejected' => 'danger', 'needs_info' => 'amber', 'cancelled' => 'neutral', default => 'accent' }">{{ __('applications.status.'.$application->status) }}</x-ui.badge>
        </header>

        @if ($application->decision_note_public)
            <div class="rounded-lg border border-border-strong bg-surface-1 p-5">
                <h2 class="text-sm font-medium text-text-subtle">{{ __('applications.decision') }}</h2>
                <p class="mt-1 whitespace-pre-line text-text">{{ $application->decision_note_public }}</p>
            </div>
        @endif

        <section aria-labelledby="h-history" class="rounded-lg border border-border bg-surface-1 p-6">
            <h2 id="h-history" class="font-display text-h4 font-semibold text-text">{{ __('applications.history') }}</h2>
            <ol class="mt-3 space-y-2 text-sm">
                @foreach ($history as $h)
                    <li>
                        <span class="text-text">{{ __('applications.status.'.$h->to_status) }}</span>
                        <time class="text-xs text-text-subtle"> · {{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d.m.Y H:i') }}</time>
                        @if ($h->note_public)<p class="mt-0.5 whitespace-pre-line text-text-muted">{{ $h->note_public }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </section>

        @if ($application->publicMessages->isNotEmpty() || $application->status === 'needs_info')
            <section aria-labelledby="h-msg" class="rounded-lg border border-border bg-surface-1 p-6">
                <h2 id="h-msg" class="font-display text-h4 font-semibold text-text">{{ __('applications.messages') }}</h2>
                <ul class="mt-3 space-y-3">
                    @foreach ($application->publicMessages as $m)
                        <li @class(['rounded-md px-4 py-3 text-sm', 'bg-surface-2' => $m->author_id === $application->user_id, 'bg-accent-soft' => $m->author_id !== $application->user_id])>
                            <p class="text-xs text-text-subtle">{{ $m->author_id === $application->user_id ? __('support.you') : __('support.staff') }} · {{ $m->created_at?->diffForHumans() }}</p>
                            <p class="mt-1 whitespace-pre-line text-text">{{ $m->body }}</p>
                        </li>
                    @endforeach
                </ul>
                @if ($application->status === 'needs_info')
                    <form method="POST" action="{{ route('panel.applications.messages.store', $application) }}" class="mt-4 space-y-3">
                        @csrf
                        <x-ui.field name="body" :label="__('applications.reply')" required><x-ui.input name="body" multiline rows="4" required /></x-ui.field>
                        <x-ui.button type="submit" size="sm" icon="send">{{ __('applications.send') }}</x-ui.button>
                    </form>
                @endif
            </section>
        @endif

        <section aria-labelledby="h-answers" class="rounded-lg border border-border bg-surface-1 p-6">
            <h2 id="h-answers" class="font-display text-h4 font-semibold text-text">{{ __('applications.answers') }}</h2>
            <dl class="mt-3 space-y-4">
                @if ($application->character)
                    <div><dt class="text-sm text-text-subtle">{{ __('applications.character') }}</dt><dd class="text-text">{{ $application->character->name }}</dd></div>
                @endif
                @foreach ($application->answers as $answer)
                    <div><dt class="text-sm text-text-subtle">{{ $answer->question_label }}</dt><dd class="whitespace-pre-line text-text">{{ $answer->answer ?? '—' }}</dd></div>
                @endforeach
            </dl>
        </section>

        @if (in_array($application->status, ['submitted', 'needs_info'], true))
            <form method="POST" action="{{ route('panel.applications.cancel', $application) }}">@csrf<x-ui.button type="submit" size="sm" variant="ghost">{{ __('applications.cancel') }}</x-ui.button></form>
        @endif
    </div>
</x-layouts.panel>
