{{-- Ticket detayı (personel) — iç notlar amber şeritle ayrılır; oyuncu görünümünde hiç yüklenmez (FR-SUP-006). --}}
@php $md = app(\App\Services\Content\MarkdownRenderer::class); @endphp
<x-layouts.admin :title="$ticket->number">
    <x-admin.header :title="$ticket->subject" :description="$ticket->number.' · '.$ticket->category->name.' · '.$ticket->user->username" :back="route('admin.support.index')" :back-label="__('support.admin.queue')">
        <x-slot:actions>
            <x-ui.badge tone="accent">{{ __('support.status.'.$ticket->status) }}</x-ui.badge>
            <x-ui.badge :tone="match ($ticket->priority) { 'urgent' => 'danger', 'high' => 'amber', default => 'neutral' }">{{ __('support.priority.'.$ticket->priority) }}</x-ui.badge>
        </x-slot:actions>
    </x-admin.header>

    @if ($ticket->reopen_requested_at)
        <div role="status" class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-md border border-amber/40 bg-amber-soft px-4 py-3 text-text">
            <span>{{ __('support.admin.reopen_pending') }}</span>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('admin.support.reopen-decision', $ticket) }}">@csrf<input type="hidden" name="decision" value="approve"><x-ui.button type="submit" size="sm">{{ __('support.admin.reopen_approve') }}</x-ui.button></form>
                <form method="POST" action="{{ route('admin.support.reopen-decision', $ticket) }}">@csrf<input type="hidden" name="decision" value="reject"><x-ui.button type="submit" size="sm" variant="secondary">{{ __('support.admin.reopen_reject') }}</x-ui.button></form>
            </div>
        </div>
    @endif

    <x-ui.realtime-banner :channels="'support.ticket.'.$ticket->ulid" event="support.message" :message="__('notifications.realtime.ticket')" />
    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <div class="space-y-4">
            <ol class="space-y-3">
                @foreach ($messages as $m)
                    <li @class(['rounded-lg border p-4', 'border-amber/50 bg-amber-soft' => $m->isInternal(), 'border-border bg-surface-1' => ! $m->isInternal() && $m->author_type === 'player', 'border-accent/30 bg-accent-soft' => ! $m->isInternal() && $m->author_type === 'staff'])>
                        <p class="text-xs text-text-subtle">
                            @if ($m->isInternal())<x-ui.badge tone="amber" icon="lock" class="mr-1">{{ __('support.internal') }}</x-ui.badge>@endif
                            <span class="font-medium text-text">{{ $m->author?->status?->value === 'anonymized' ? __('support.deleted_user') : ($m->author?->username ?? __('support.system')) }}</span>
                            · {{ $m->author_type === 'player' ? __('support.admin.player') : __('support.staff') }}
                            · <time datetime="{{ $m->created_at->toIso8601String() }}">{{ $m->created_at->format('d.m.Y H:i') }}</time>
                        </p>
                        <x-ui.prose class="mt-2 text-sm" :html="$md->render($m->body)" />
                        @if ($m->attachments->isNotEmpty())
                            <ul class="mt-2 flex flex-wrap gap-2">
                                @foreach ($m->attachments as $a)
                                    <li><a href="{{ route('admin.support.attachments.show', $a) }}" class="inline-flex items-center gap-1.5 rounded-md border border-border px-2.5 py-1 text-xs text-text hover:border-accent"><x-lucide-paperclip class="size-3.5" aria-hidden="true" />{{ $a->original_name }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ol>

            @can('support.reply')
                <x-admin.card :title="__('support.reply')">
                    @if ($macros->isNotEmpty())
                        <details class="mb-3">
                            <summary class="cursor-pointer text-xs text-accent">{{ __('support.admin.macro') }} ({{ $macros->count() }})</summary>
                            <ul class="mt-2 space-y-2">
                                @foreach ($macros as $macro)
                                    <li class="rounded-md border border-border p-2">
                                        <p class="text-xs font-medium text-text">{{ $macro->title }}</p>
                                        <pre class="mt-1 font-mono text-[11px] whitespace-pre-wrap text-text-muted">{{ $macro->body }}</pre>
                                    </li>
                                @endforeach
                            </ul>
                            <p class="mt-2 text-[11px] text-text-subtle">Makro metnini kopyala; gönderirken @{{ player.username }}, @{{ ticket.number }} gibi değişkenler doldurulur.</p>
                        </details>
                    @endif
                    <form method="POST" action="{{ route('admin.support.messages.store', $ticket) }}" class="space-y-3">
                        @csrf
                        <x-ui.field name="body" label="Mesaj" required><x-ui.input name="body" multiline rows="6" maxlength="5000" required /></x-ui.field>
                        <div class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-2 text-text"><input type="radio" name="visibility" value="public" checked class="size-4 accent-[var(--color-accent)]">Oyuncuya yanıt</label>
                            @can('support.internal_note')
                                <label class="flex items-center gap-2 text-text"><input type="radio" name="visibility" value="internal" class="size-4 accent-[var(--color-amber)]">{{ __('support.internal') }}</label>
                            @endcan
                            <x-ui.button type="submit" size="sm" icon="send" class="ml-auto">{{ __('support.send') }}</x-ui.button>
                        </div>
                        <p class="text-xs text-text-subtle">{{ __('support.internal_hint') }}</p>
                    </form>
                </x-admin.card>
            @endcan
        </div>

        <aside class="space-y-4">
            <x-admin.card>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-2"><dt class="text-text-subtle">{{ __('support.admin.branch') }}</dt><dd class="text-text">{{ $ticket->branch->name }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-text-subtle">{{ __('support.admin.assigned') }}</dt><dd class="text-text">{{ $ticket->assignee?->username ?? __('support.admin.unassigned') }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-text-subtle">Oluşturuldu</dt><dd class="text-text">{{ $ticket->created_at?->format('d.m.Y H:i') }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-text-subtle">İlk yanıt</dt><dd class="text-text">{{ $ticket->first_staff_response_at?->diffForHumans($ticket->created_at, true) ?? '—' }}</dd></div>
                </dl>
                @can('support.reply')
                    @if ($ticket->assigned_to !== auth()->id())
                        <form method="POST" action="{{ route('admin.support.claim', $ticket) }}" class="mt-4">@csrf<x-ui.button type="submit" size="sm" variant="secondary" class="w-full" icon="hand">{{ __('support.admin.claim') }}</x-ui.button></form>
                    @endif
                    <form method="POST" action="{{ route('admin.support.status', $ticket) }}" class="mt-3 flex gap-2">@csrf
                        <label class="sr-only" for="f-status">Durum</label>
                        <select id="f-status" name="status" class="h-9 min-w-0 flex-1 rounded-md border border-border-input bg-surface-1 px-2 text-sm text-text">
                            @foreach (__('support.status') as $value => $label)
                                @if ($value !== 'closed' || auth()->user()->can('support.close'))<option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>@endif
                            @endforeach
                        </select>
                        <x-ui.button type="submit" size="sm" variant="secondary">{{ __('support.admin.update') }}</x-ui.button>
                    </form>
                @endcan
            </x-admin.card>

            @can('support.assign')
                <x-admin.card>
                    <form method="POST" action="{{ route('admin.support.assign', $ticket) }}" class="space-y-2">@csrf
                        <x-ui.field name="assignee" :label="__('support.admin.assignee')"><x-ui.input name="assignee" maxlength="254" placeholder="kullanıcı adı (boş = kaldır)" /></x-ui.field>
                        <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">{{ __('support.admin.assign') }}</x-ui.button>
                    </form>
                    <form method="POST" action="{{ route('admin.support.priority', $ticket) }}" class="mt-4 flex gap-2">@csrf
                        <label class="sr-only" for="f-priority">Öncelik</label>
                        <select id="f-priority" name="priority" class="h-9 min-w-0 flex-1 rounded-md border border-border-input bg-surface-1 px-2 text-sm text-text">
                            @foreach (__('support.priority') as $value => $label)<option value="{{ $value }}" @selected($ticket->priority === $value)>{{ $label }}</option>@endforeach
                        </select>
                        <x-ui.button type="submit" size="sm" variant="secondary">{{ __('support.admin.update') }}</x-ui.button>
                    </form>
                    <details class="mt-4">
                        <summary class="cursor-pointer text-sm text-accent">{{ __('support.admin.escalate') }}</summary>
                        <form method="POST" action="{{ route('admin.support.escalate', $ticket) }}" class="mt-3 space-y-2">@csrf
                            <label class="sr-only" for="f-branch">Branş</label>
                            <select id="f-branch" name="branch_id" class="h-9 w-full rounded-md border border-border-input bg-surface-1 px-2 text-sm text-text">
                                @foreach ($branches as $id => $name)@if ($id !== $ticket->branch_id)<option value="{{ $id }}">{{ $name }}</option>@endif @endforeach
                            </select>
                            <x-ui.field name="note" :label="__('support.admin.escalate_note')" required><x-ui.input name="note" maxlength="500" required /></x-ui.field>
                            <x-ui.button type="submit" size="sm" variant="secondary" class="w-full">{{ __('support.admin.escalate') }}</x-ui.button>
                        </form>
                    </details>
                </x-admin.card>
            @endcan

            <x-admin.card :title="__('support.admin.events')" flush>
                <ol class="divide-y divide-border text-xs">
                    @foreach ($events as $e)
                        <li class="px-4 py-2">
                            <span class="text-text">{{ __('support.events.'.$e->type) }}</span>
                            @if ($e->to_value)<span class="text-text-muted"> → {{ __('support.status.'.$e->to_value) !== 'support.status.'.$e->to_value ? __('support.status.'.$e->to_value) : $e->to_value }}</span>@endif
                            <span class="block text-text-subtle">{{ $e->actor?->username ?? __('support.system') }} · {{ $e->created_at->format('d.m H:i') }}</span>
                            @if ($e->note)<span class="block text-text-muted">{{ $e->note }}</span>@endif
                        </li>
                    @endforeach
                </ol>
            </x-admin.card>
        </aside>
    </div>
</x-layouts.admin>
