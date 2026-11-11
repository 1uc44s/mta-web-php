{{-- Ticket (oyuncu görünümü) — yalnızca public mesajlar yüklenir (FR-SUP-006). Gövde Markdown, sanitize edilerek. --}}
@php $md = app(\App\Services\Content\MarkdownRenderer::class); @endphp
<x-layouts.panel :title="$ticket->number">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('panel.support.index') }}" class="text-sm text-text-muted hover:text-accent">← {{ __('support.my_tickets') }}</a>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="font-mono text-sm text-text-subtle">{{ $ticket->number }}</span>
            <x-ui.badge tone="accent">{{ __('support.status.'.$ticket->status) }}</x-ui.badge>
            <span class="text-sm text-text-muted">{{ $ticket->category->name }}</span>
        </div>
        <h1 class="mt-2 font-display text-h2 font-bold text-text">{{ $ticket->subject }}</h1>

        <ol class="mt-8 space-y-4">
            @foreach ($messages as $m)
                @php $mine = $m->author_type === 'player'; @endphp
                <li @class(['rounded-lg border p-5', 'border-border bg-surface-1' => $mine, 'border-accent/30 bg-accent-soft' => ! $mine])>
                    <p class="text-xs text-text-subtle">
                        <span class="font-medium text-text">{{ $mine ? __('support.you') : ($m->author_type === 'system' ? __('support.system') : __('support.staff').' · '.($m->author?->status?->value === 'anonymized' ? __('support.deleted_user') : $m->author?->username)) }}</span>
                        · <time datetime="{{ $m->created_at->toIso8601String() }}">{{ $m->created_at->format('d.m.Y H:i') }}</time>
                    </p>
                    <x-ui.prose class="mt-2 text-sm" :html="$md->render($m->body)" />
                    @if ($m->attachments->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($m->attachments as $a)
                                <li><a href="{{ route('panel.support.attachments.show', $a) }}" class="inline-flex items-center gap-1.5 rounded-md border border-border px-2.5 py-1 text-xs text-text hover:border-accent"><x-lucide-paperclip class="size-3.5" aria-hidden="true" />{{ $a->original_name }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ol>

        @if ($ticket->status === 'closed')
            <div class="mt-8 rounded-lg border border-border p-5 text-sm text-text-muted">
                <p>{{ __('support.closed_note') }}</p>
                @if ($ticket->reopen_requested_at)
                    <p class="mt-2 text-text">{{ __('support.reopen_requested') }}</p>
                @elseif ($ticket->closed_at && $ticket->closed_at->gt(now()->subDays(7)))
                    <p class="mt-1">{{ __('support.reopen_hint') }}</p>
                    <form method="POST" action="{{ route('panel.support.reopen-request', $ticket) }}" class="mt-3">@csrf<x-ui.button type="submit" size="sm" variant="secondary">{{ __('support.reopen') }}</x-ui.button></form>
                @endif
            </div>
        @else
            <form method="POST" action="{{ route('panel.support.messages.store', $ticket) }}" enctype="multipart/form-data" class="mt-8 space-y-4 rounded-lg border border-border bg-surface-1 p-5">
                @csrf
                <x-ui.field name="body" :label="__('support.reply')" required><x-ui.input name="body" multiline rows="5" maxlength="5000" required /></x-ui.field>
                <x-ui.field name="attachments" :label="__('support.attachments')">
                    <input id="f-attachments" name="attachments[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" class="block w-full text-sm text-text file:mr-3 file:rounded-md file:border-0 file:bg-surface-2 file:px-3 file:py-2 file:text-text">
                </x-ui.field>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit" icon="send" data-submit-once>{{ __('support.send') }}</x-ui.button>
                </div>
            </form>
            <form method="POST" action="{{ route('panel.support.close', $ticket) }}" class="mt-3">@csrf
                <button type="submit" class="text-sm text-text-muted hover:text-danger">{{ __('support.close') }}</button>
            </form>
        @endif
    </div>
</x-layouts.panel>
