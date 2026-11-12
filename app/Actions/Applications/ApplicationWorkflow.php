<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Enums\AuditAction;
use App\Models\Application;
use App\Models\ApplicationMessage;
use App\Models\ApplicationType;
use App\Models\Character;
use App\Models\GameAccountLink;
use App\Models\User;
use App\Notifications\PanelNotice;
use App\Services\Audit\AuditLogger;
use App\Support\ApplicationForms\ApplicationForms;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Başvuru durum makinesi — docs/SRS.md FR-APP-001..006.
 *   draft → submitted → in_review → (needs_info ⇄ in_review) → approved | rejected
 *   Oyuncu draft/submitted/needs_info durumlarında iptal edebilir. Her geçiş `application_status_history` (append-only)
 *   tablosuna; karar ayrıca audit'e yazılır. Tür başına tek açık başvuru DB'de (`uq_applications_open_key`).
 */
final class ApplicationWorkflow
{
    /** @var array<string, list<string>> İnceleyen tarafın izinli geçişleri. */
    private const STAFF_TRANSITIONS = [
        'submitted' => ['in_review', 'needs_info', 'approved', 'rejected'],
        'in_review' => ['needs_info', 'approved', 'rejected'],
        'needs_info' => ['in_review', 'approved', 'rejected'],
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /** FR-APP-001/004: uygunluk. Null = uygun; aksi halde gösterilecek mesaj. */
    public function eligibility(User $user, ApplicationType $type): ?string
    {
        return match (true) {
            ! $type->is_open => __('applications.errors.closed'),
            $type->requires_linked_account && ! GameAccountLink::query()->where('user_id', $user->id)->whereIn('status', GameAccountLink::ACTIVE)->exists() => __('applications.errors.link_required'),
            $type->min_account_age_days > 0 && $user->created_at !== null && $user->created_at->gt(now()->subDays($type->min_account_age_days)) => __('applications.errors.account_age', ['days' => $type->min_account_age_days]),
            Application::query()->where('user_id', $user->id)->where('application_type_id', $type->id)->whereIn('status', Application::OPEN)->exists() => __('applications.errors.open_exists'),
            $this->inCooldown($user, $type) => __('applications.errors.cooldown', ['days' => $type->cooldown_days]),
            default => null,
        };
    }

    /**
     * Taslak oluşturur / günceller; `$submit` ise gönderir. Cevaplar soru etiketinin anlık kopyasıyla yazılır (FR-APP-002).
     *
     * @param  array<string, mixed>  $answers
     */
    public function save(User $user, ApplicationType $type, ?Application $application, array $answers, ?string $characterUlid, bool $submit): Application
    {
        if ($application === null && ($error = $this->eligibility($user, $type)) !== null) {
            throw ValidationException::withMessages(['type' => $error]);
        }
        if ($application !== null && $application->status !== 'draft') {
            throw ValidationException::withMessages(['type' => __('applications.errors.not_editable')]);
        }

        $character = $characterUlid !== null ? Character::query()->ownedBy($user)->where('ulid', $characterUlid)->first() : null;
        if ($characterUlid !== null && $character === null) {
            throw ValidationException::withMessages(['character' => __('applications.errors.character')]);
        }
        if ($submit && $type->requires_character && $character === null && $application?->character_id === null) {
            throw ValidationException::withMessages(['character' => __('applications.errors.character_required')]);
        }

        return DB::transaction(function () use ($user, $type, $application, $answers, $character, $submit): Application {
            if ($application === null) {
                try {
                    $application = Application::query()->create(['user_id' => $user->id, 'application_type_id' => $type->id, 'status' => 'draft']);
                } catch (UniqueConstraintViolationException) {
                    throw ValidationException::withMessages(['type' => __('applications.errors.open_exists')]);
                }
                $this->history($application, null, 'draft', $user);
            }

            if ($character !== null) {
                $application->forceFill(['character_id' => $character->id])->save();
            }

            foreach (ApplicationForms::fields($type->form_key) as $key => $def) {
                if (array_key_exists($key, $answers)) {
                    $value = $answers[$key];
                    $application->answers()->updateOrCreate(['field_key' => $key], [
                        'question_label' => $def['label'],
                        'answer' => $value === null || $value === '' ? null : (string) $value,
                    ]);
                }
            }

            if ($submit) {
                $application->forceFill(['status' => 'submitted', 'submitted_at' => now()])->save();
                $this->history($application, 'draft', 'submitted', $user);
            }

            return $application;
        });
    }

    public function cancel(User $user, Application $application): void
    {
        if (! in_array($application->status, ['draft', 'submitted', 'needs_info'], true)) {
            throw ValidationException::withMessages(['status' => __('applications.errors.not_cancellable')]);
        }

        DB::transaction(function () use ($user, $application): void {
            $from = $application->status;
            $application->forceFill(['status' => 'cancelled'])->save();
            $this->history($application, $from, 'cancelled', $user);
        });
    }

    /** Oyuncunun `needs_info` yanıtı: public mesaj + `in_review`'a dönüş. */
    public function playerReply(User $user, Application $application, string $body): void
    {
        if ($application->status !== 'needs_info') {
            throw ValidationException::withMessages(['body' => __('applications.errors.no_info_requested')]);
        }

        DB::transaction(function () use ($user, $application, $body): void {
            ApplicationMessage::query()->create(['application_id' => $application->id, 'author_id' => $user->id, 'visibility' => 'public', 'body' => $body]);
            $application->forceFill(['status' => 'in_review'])->save();
            $this->history($application, 'needs_info', 'in_review', $user);
        });

        $application->assignee?->notify(new PanelNotice('application_replied', __('applications.notice.replied'), route('admin.applications.show', $application)));
    }

    public function staffMessage(User $actor, Application $application, string $body, string $visibility): void
    {
        $internal = $visibility === 'internal';
        ApplicationMessage::query()->create(['application_id' => $application->id, 'author_id' => $actor->id, 'visibility' => $internal ? 'internal' : 'public', 'body' => $body]);

        if (! $internal) {
            $application->user->notify(new PanelNotice('application_message', __('applications.notice.message'), route('panel.applications.show', $application)));
        }
    }

    public function assign(Application $application, ?User $assignee): void
    {
        if ($assignee !== null && (! $assignee->hasPermission('applications.review') || ! Application::query()->reviewableBy($assignee)->whereKey($application->id)->exists())) {
            throw ValidationException::withMessages(['assigned_to' => __('applications.errors.assignee')]);
        }

        $application->forceFill(['assigned_to' => $assignee?->id])->save();
    }

    /** FR-APP-006: karar herkese açık not (oyuncu görür) + iç not (görmez). */
    public function transition(User $actor, Application $application, string $to, ?string $notePublic, ?string $noteInternal): void
    {
        $from = $application->status;

        if (! in_array($to, self::STAFF_TRANSITIONS[$from] ?? [], true)) {
            throw ValidationException::withMessages(['status' => __('applications.errors.transition')]);
        }
        if (in_array($to, ['needs_info', 'rejected'], true) && ($notePublic === null || trim($notePublic) === '')) {
            throw ValidationException::withMessages(['note_public' => __('applications.errors.note_required')]);
        }

        $decided = in_array($to, ['approved', 'rejected'], true);

        DB::transaction(function () use ($actor, $application, $from, $to, $notePublic, $noteInternal, $decided): void {
            $application->forceFill(['status' => $to, 'assigned_to' => $application->assigned_to ?? $actor->id])->save();

            if ($decided) {
                $application->forceFill(['decided_at' => now(), 'decided_by' => $actor->id, 'decision_note_public' => $notePublic])->save();
            }

            $this->history($application, $from, $to, $actor, $notePublic, $noteInternal);
        });

        if ($decided) {
            $this->audit->record(AuditAction::ApplicationsDecided, $application, ['before' => ['status' => $from], 'after' => ['status' => $to]],
                $noteInternal ?: ($notePublic ?: __('applications.status.'.$to)), $actor);
        }

        if (in_array($to, ['needs_info', 'approved', 'rejected'], true)) {
            $application->user->notify(new PanelNotice('application_'.$to, __('applications.notice.'.$to, ['type' => $application->type->name]), route('panel.applications.show', $application)));
        }
    }

    /**
     * Oyuncuya gösterilen süreç: yalnızca durum ve herkese açık not (iç not ve aktör asla).
     *
     * @return Collection<int, \stdClass>
     */
    public function publicHistory(Application $application): Collection
    {
        return DB::table('application_status_history')->where('application_id', $application->id)->orderBy('id')
            ->get(['to_status', 'note_public', 'created_at']);
    }

    /**
     * ACP görünümü: tüm geçişler (iç not + aktör adı).
     *
     * @return Collection<int, \stdClass>
     */
    public function staffHistory(Application $application): Collection
    {
        return DB::table('application_status_history as h')->leftJoin('users as u', 'u.id', '=', 'h.actor_id')
            ->where('h.application_id', $application->id)->orderBy('h.id')
            ->get(['h.from_status', 'h.to_status', 'h.note_public', 'h.note_internal', 'h.created_at', 'u.username']);
    }

    private function inCooldown(User $user, ApplicationType $type): bool
    {
        if ($type->cooldown_days === 0) {
            return false;
        }

        return Application::query()->where('user_id', $user->id)->where('application_type_id', $type->id)
            ->where('status', 'rejected')->where('decided_at', '>', now()->subDays($type->cooldown_days))->exists();
    }

    private function history(Application $application, ?string $from, string $to, ?User $actor, ?string $notePublic = null, ?string $noteInternal = null): void
    {
        DB::table('application_status_history')->insert([
            'application_id' => $application->id, 'from_status' => $from, 'to_status' => $to, 'actor_id' => $actor?->id,
            'note_public' => $notePublic !== null ? mb_substr($notePublic, 0, 2000) : null,
            'note_internal' => $noteInternal !== null ? mb_substr($noteInternal, 0, 2000) : null,
            'created_at' => now(),
        ]);
    }
}
