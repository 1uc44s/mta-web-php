{{-- Başvuru inceleme — docs/SRS.md FR-APP-005/006. İç mesajlar DESIGN §6.3 iç not görünümüyle ayrışır. --}}
<x-layouts.admin :title="$application->type->name.' · '.$application->user->username">
    <x-admin.header :title="$application->type->name" :description="$application->user->username.' · '.__('applications.status.'.$application->status)" :back="route('admin.applications.index')" :back-label="__('applications.admin.queue')" />

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
            <x-admin.card :title="__('applications.answers')">
                <dl class="space-y-4">
                    @if ($application->character)
                        <div><dt class="text-sm text-text-subtle">{{ __('applications.character') }}</dt><dd><a class="text-text hover:text-accent" href="{{ route('admin.characters.show', $application->character->ulid) }}">{{ $application->character->name }}</a></dd></div>
                    @endif
                    @foreach ($application->answers as $answer)
                        <div><dt class="text-sm text-text-subtle">{{ $answer->question_label }}</dt><dd class="whitespace-pre-line text-text">{{ $answer->answer ?? '—' }}</dd></div>
                    @endforeach
                </dl>
            </x-admin.card>

            <x-admin.card :title="__('applications.messages')">
                <ul class="space-y-3">
                    @forelse ($application->messages as $m)
                        <li @class([
                            'rounded-md px-4 py-3 text-sm',
                            'border border-dashed border-amber bg-amber/5' => $m->visibility === 'internal',
                            'bg-surface-2' => $m->visibility === 'public' && $m->author_id === $application->user_id,
                            'bg-accent-soft' => $m->visibility === 'public' && $m->author_id !== $application->user_id,
                        ])>
                            <p class="text-xs text-text-subtle">{{ $m->author?->username ?? __('support.system') }} · {{ $m->created_at?->diffForHumans() }}
                                @if ($m->visibility === 'internal') · <span class="text-amber">{{ __('applications.admin.internal') }}</span>@endif</p>
                            <p class="mt-1 whitespace-pre-line text-text">{{ $m->body }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-text-muted">{{ __('admin.none') }}</li>
                    @endforelse
                </ul>
                @if ($canReview)
                    <form method="POST" action="{{ route('admin.applications.messages.store', $application) }}" class="mt-4 space-y-3">
                        @csrf
                        <x-ui.field name="body" :label="__('applications.messages')" required><x-ui.input name="body" multiline rows="3" required /></x-ui.field>
                        <div class="flex flex-wrap items-center gap-4 text-sm text-text">
                            <label class="flex items-center gap-2"><input type="radio" name="visibility" value="public" checked> {{ __('applications.admin.public') }}</label>
                            <label class="flex items-center gap-2"><input type="radio" name="visibility" value="internal"> {{ __('applications.admin.internal') }}</label>
                            <x-ui.button type="submit" size="sm" variant="secondary">{{ __('applications.send') }}</x-ui.button>
                        </div>
                    </form>
                @endif
            </x-admin.card>
        </div>

        <div class="space-y-6">
            @if ($canReview && in_array($application->status, ['submitted', 'in_review', 'needs_info'], true))
                <x-admin.card :title="__('applications.admin.new_status')">
                    <form method="POST" action="{{ route('admin.applications.status', $application) }}" class="space-y-3">
                        @csrf
                        <x-ui.field name="status" :label="__('applications.admin.new_status')" required>
                            <select id="f-status" name="status" class="h-10 rounded-sm border border-border-input bg-surface-2 px-3 text-sm text-text">
                                @foreach (['in_review', 'needs_info', 'approved', 'rejected'] as $s)
                                    @if ($s !== $application->status)<option value="{{ $s }}">{{ __('applications.status.'.$s) }}</option>@endif
                                @endforeach
                            </select>
                        </x-ui.field>
                        <x-ui.field name="note_public" :label="__('applications.admin.note_public')"><x-ui.input name="note_public" multiline rows="3" /></x-ui.field>
                        <x-ui.field name="note_internal" :label="__('applications.admin.note_internal')"><x-ui.input name="note_internal" multiline rows="2" /></x-ui.field>
                        <x-ui.button type="submit" size="sm">{{ __('applications.admin.apply_status') }}</x-ui.button>
                    </form>
                </x-admin.card>
            @endif

            <x-admin.card>
                <p class="text-sm text-text-subtle">{{ __('applications.admin.assigned') }}: <span class="text-text">{{ $application->assignee?->username ?? '—' }}</span></p>
                @if ($canReview)
                    <form method="POST" action="{{ route('admin.applications.assign', $application) }}" class="mt-3 flex gap-2">
                        @csrf
                        <x-ui.button type="submit" size="sm" variant="secondary">{{ __('applications.admin.assign_me') }}</x-ui.button>
                        @if ($application->assigned_to)<x-ui.button type="submit" size="sm" variant="ghost" name="unassign" value="1">{{ __('applications.admin.unassign') }}</x-ui.button>@endif
                    </form>
                @endif
            </x-admin.card>

            <x-admin.card :title="__('applications.history')">
                <ol class="space-y-2 text-sm">
                    @foreach ($history as $h)
                        <li>
                            <span class="text-text">{{ __('applications.status.'.$h->to_status) }}</span>
                            <span class="text-xs text-text-subtle">· {{ $h->username ?? __('support.system') }} · {{ \Illuminate\Support\Carbon::parse($h->created_at)->format('d.m.Y H:i') }}</span>
                            @if ($h->note_public)<p class="text-text-muted">{{ $h->note_public }}</p>@endif
                            @if ($h->note_internal)<p class="text-amber">{{ $h->note_internal }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </x-admin.card>
        </div>
    </div>
</x-layouts.admin>
