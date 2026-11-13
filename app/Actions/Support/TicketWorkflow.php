<?php

declare(strict_types=1);

namespace App\Actions\Support;

use App\Enums\AuditAction;
use App\Events\SupportMessagePosted;
use App\Events\SupportQueueChanged;
use App\Models\Branch;
use App\Models\SupportCategory;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\SupportTicketEvent;
use App\Models\User;
use App\Notifications\SupportActivity;
use App\Services\Audit\AuditLogger;
use App\Services\Messaging\MessageDispatcher;
use App\Services\Notifications\NotificationPreferences;
use App\Support\Realtime;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Destek talebi yaşam döngüsü — docs/SRS.md FR-SUP-001..011, DATABASE §8.
 * Durum makinesi: open → in_progress → waiting_for_player ⇄ waiting_for_staff → resolved → closed.
 */
final class TicketWorkflow
{
    public const REOPEN_WINDOW_DAYS = 7;

    public function __construct(
        private readonly StoreSupportAttachment $attachments,
        private readonly AuditLogger $audit,
        private readonly MessageDispatcher $messages,
        private readonly NotificationPreferences $preferences,
    ) {}

    /**
     * FR-SUP-001/003/004: idempotent oluşturma. Aynı anahtarla ikinci istek ilk ticket'ı döner.
     *
     * @param  list<UploadedFile>  $files
     */
    public function create(User $player, SupportCategory $category, string $subject, string $body, string $idempotencyKey, array $files = []): SupportTicket
    {
        $existing = SupportTicket::query()->where(['user_id' => $player->id, 'idempotency_key' => $idempotencyKey])->first();
        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($player, $category, $subject, $body, $idempotencyKey, $files): SupportTicket {
            $now = now();
            $ticket = SupportTicket::query()->create([
                'ulid' => strtolower((string) Str::ulid()),
                'number' => $this->nextNumber(),
                'user_id' => $player->id,
                'category_id' => $category->id,
                'branch_id' => $category->branch_id,
                'subject' => $subject,
                'status' => 'open',
                'priority' => $category->default_priority,
                'idempotency_key' => $idempotencyKey,
                'last_message_at' => $now,
                'last_player_message_at' => $now,
                'staff_unread' => true,
            ]);

            $message = $ticket->messages()->create(['author_id' => $player->id, 'author_type' => 'player', 'visibility' => SupportMessage::PUBLIC, 'body' => $body]);
            foreach ($files as $file) {
                $this->attachments->handle($file, $message, $player);
            }

            $this->event($ticket, $player, 'created', null, 'open');
            $this->broadcast($ticket, null);

            return $ticket;
        });
    }

    /** @param  list<UploadedFile>  $files */
    public function addPlayerMessage(SupportTicket $ticket, User $player, string $body, array $files = []): void
    {
        if (in_array($ticket->status, ['closed'], true)) {
            throw ValidationException::withMessages(['body' => __('support.errors.closed')]);
        }

        DB::transaction(function () use ($ticket, $player, $body, $files): void {
            $message = $ticket->messages()->create(['author_id' => $player->id, 'author_type' => 'player', 'visibility' => SupportMessage::PUBLIC, 'body' => $body]);
            foreach ($files as $file) {
                $this->attachments->handle($file, $message, $player);
            }

            $from = $ticket->status;
            $to = in_array($from, ['waiting_for_player', 'resolved'], true) ? 'waiting_for_staff' : $from;
            $ticket->update(['status' => $to, 'last_message_at' => now(), 'last_player_message_at' => now(), 'staff_unread' => true, 'resolved_at' => $to === 'resolved' ? $ticket->resolved_at : null]);

            if ($from !== $to) {
                $this->event($ticket, $player, 'status_changed', $from, $to);
            }
            $this->broadcast($ticket, 'player');
        });

        if ($ticket->assigned_to !== null) {
            $ticket->assignee?->notify(new SupportActivity($ticket, 'player_messaged'));
        }
    }

    /** FR-SUP-005/006/010: public yanıt → waiting_for_player + oyuncuya bildirim; iç not durum değiştirmez ve bildirim üretmez. */
    public function addStaffMessage(SupportTicket $ticket, User $staff, string $body, bool $internal): SupportMessage
    {
        $message = DB::transaction(function () use ($ticket, $staff, $body, $internal): SupportMessage {
            $message = $ticket->messages()->create([
                'author_id' => $staff->id, 'author_type' => 'staff',
                'visibility' => $internal ? SupportMessage::INTERNAL : SupportMessage::PUBLIC, 'body' => $body,
            ]);

            if (! $internal) {
                $from = $ticket->status;
                $ticket->update([
                    'status' => 'waiting_for_player',
                    'last_message_at' => now(),
                    'last_staff_message_at' => now(),
                    'first_staff_response_at' => $ticket->first_staff_response_at ?? now(),
                    'player_unread' => true,
                    'staff_unread' => false,
                    'assigned_to' => $ticket->assigned_to ?? $staff->id,
                ]);
                if ($from !== 'waiting_for_player') {
                    $this->event($ticket, $staff, 'status_changed', $from, 'waiting_for_player');
                }
                $this->broadcast($ticket, 'staff');
            }

            return $message;
        });

        if (! $internal) {
            $player = $ticket->user;
            $player->notify(new SupportActivity($ticket, 'staff_replied'));

            if ($this->preferences->allows($player, 'support', 'mail')) {
                $this->messages->send('support.staff_replied', $player, [
                    'user.username' => $player->username, 'ticket.number' => $ticket->number,
                    'ticket.subject' => $ticket->subject, 'link.ticket' => route('panel.support.show', $ticket),
                ]);
            }
        }

        return $message;
    }

    public function changeStatus(SupportTicket $ticket, User $actor, string $status): void
    {
        if (! in_array($status, SupportTicket::STATUSES, true) || $status === $ticket->status) {
            throw ValidationException::withMessages(['status' => __('support.errors.status')]);
        }

        $from = $ticket->status;
        $ticket->update([
            'status' => $status,
            'resolved_at' => $status === 'resolved' ? now() : ($status === 'closed' ? $ticket->resolved_at : null),
            'closed_at' => $status === 'closed' ? now() : null,
            'closed_by' => $status === 'closed' ? $actor->id : null,
            'reopen_requested_at' => null,
        ]);
        $this->event($ticket, $actor, 'status_changed', $from, $status);
    }

    /** Oyuncu kendi ticket'ını kapatır (FR-SUP-005). */
    public function closeByPlayer(SupportTicket $ticket, User $player): void
    {
        if ($ticket->status === 'closed') {
            return;
        }
        $this->changeStatus($ticket, $player, 'closed');
    }

    /** FR-SUP-011: kapanıştan sonra 7 gün içinde yeniden açma talebi. */
    public function requestReopen(SupportTicket $ticket, User $player): void
    {
        if ($ticket->status !== 'closed' || $ticket->closed_at === null || $ticket->closed_at->lt(now()->subDays(self::REOPEN_WINDOW_DAYS))) {
            throw ValidationException::withMessages(['reopen' => __('support.errors.reopen_window')]);
        }

        $ticket->update(['reopen_requested_at' => now(), 'staff_unread' => true]);
        $this->event($ticket, $player, 'reopen_requested', null, null);
    }

    public function decideReopen(SupportTicket $ticket, User $staff, bool $approve): void
    {
        if ($ticket->reopen_requested_at === null) {
            throw ValidationException::withMessages(['reopen' => __('support.errors.no_reopen_request')]);
        }

        if ($approve) {
            $ticket->update(['status' => 'open', 'closed_at' => null, 'closed_by' => null, 'resolved_at' => null, 'reopen_requested_at' => null]);
            $this->event($ticket, $staff, 'reopened', 'closed', 'open');
        } else {
            $ticket->update(['reopen_requested_at' => null]);
            $this->event($ticket, $staff, 'status_changed', 'closed', 'closed', 'Yeniden açma talebi reddedildi');
        }
    }

    /** FR-SUP-008: atanan kişi ticket'ın branşının üyesi olmalı (veya tüm branşları görebilmeli). */
    public function assign(SupportTicket $ticket, User $actor, ?User $assignee): void
    {
        if ($assignee !== null && ! ($assignee->can('support.reply') && ($assignee->can('support.all_branches') || in_array($ticket->branch_id, $assignee->activeBranchIds(), true)))) {
            throw ValidationException::withMessages(['assignee' => __('support.errors.assignee')]);
        }

        $from = $ticket->assigned_to;
        $ticket->update(['assigned_to' => $assignee?->id, 'status' => $ticket->status === 'open' && $assignee !== null ? 'in_progress' : $ticket->status]);
        $this->event($ticket, $actor, $assignee === null ? 'unassigned' : 'assigned', $from !== null ? (string) $from : null, $assignee !== null ? (string) $assignee->id : null);
    }

    public function changePriority(SupportTicket $ticket, User $actor, string $priority): void
    {
        $from = $ticket->priority;
        $ticket->update(['priority' => $priority]);
        $this->event($ticket, $actor, 'priority_changed', $from, $priority);
    }

    /** FR-SUP-008: escalation — atama sıfırlanır, olay + audit (gerekçe zorunlu). */
    public function escalate(SupportTicket $ticket, User $actor, Branch $target, string $note): void
    {
        $from = $ticket->branch_id;
        $ticket->update(['branch_id' => $target->id, 'assigned_to' => null, 'staff_unread' => true]);
        $this->event($ticket, $actor, 'escalated', (string) $from, (string) $target->id, $note);
        $this->audit->record(AuditAction::SupportEscalated, $ticket, ['before' => ['branch_id' => $from], 'after' => ['branch_id' => $target->id]], $note, $actor);
    }

    /** FR-SUP-005: zamanlayıcı geçişleri — 7 gün yanıtsız waiting_for_player → resolved, 7 gün resolved → closed. */
    public function autoTransition(int $resolveDays, int $closeDays): int
    {
        $count = 0;

        SupportTicket::query()->where('status', 'waiting_for_player')->where('last_staff_message_at', '<=', now()->subDays($resolveDays))
            ->each(function (SupportTicket $t) use (&$count): void {
                $t->update(['status' => 'resolved', 'resolved_at' => now()]);
                $this->event($t, null, 'auto_resolved', 'waiting_for_player', 'resolved');
                $count++;
            });

        SupportTicket::query()->where('status', 'resolved')->where('resolved_at', '<=', now()->subDays($closeDays))
            ->each(function (SupportTicket $t) use (&$count): void {
                $t->update(['status' => 'closed', 'closed_at' => now()]);
                $this->event($t, null, 'auto_closed', 'resolved', 'closed');
                $count++;
            });

        return $count;
    }

    /** FR-SUP-004: `T-000123` — tek satırlık sayaçla yarışsız. */
    /** Gerçek zaman (Faz 10): yalnızca Reverb açıkken; commit sonrası yayınlanır, gövde içermez. */
    private function broadcast(SupportTicket $ticket, ?string $author): void
    {
        if (! Realtime::enabled()) {
            return;
        }
        if ($author !== null) {
            SupportMessagePosted::dispatch($ticket->ulid, $author);
        }
        SupportQueueChanged::dispatch($ticket->branch_id);
    }

    private function nextNumber(): string
    {
        DB::update('UPDATE support_ticket_sequence SET n = LAST_INSERT_ID(n + 1) WHERE id = 1');
        $n = (int) DB::scalar('SELECT LAST_INSERT_ID()');

        return 'T-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    private function event(SupportTicket $ticket, ?User $actor, string $type, ?string $from, ?string $to, ?string $note = null): void
    {
        SupportTicketEvent::query()->create([
            'ticket_id' => $ticket->id, 'actor_id' => $actor?->id, 'type' => $type,
            'from_value' => $from, 'to_value' => $to, 'note' => $note !== null ? mb_substr($note, 0, 500) : null,
        ]);
    }
}
