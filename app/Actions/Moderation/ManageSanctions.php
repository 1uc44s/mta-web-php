<?php

declare(strict_types=1);

namespace App\Actions\Moderation;

use App\Actions\Bridge\GameCommandQueue;
use App\Enums\AuditAction;
use App\Enums\UserStatus;
use App\Models\Character;
use App\Models\GameAccountLink;
use App\Models\MtaAccount;
use App\Models\Sanction;
use App\Models\SanctionAppeal;
use App\Models\User;
use App\Notifications\PanelNotice;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cezalar ve itirazlar — docs/SRS.md FR-MOD-001..006, ROUTES §6.7 / §5.6, ADMIN §8.
 * Oyun içi etkili türler `game_commands` kuyruğuna idempotent (`sanction:{ulid}:apply|revoke`) eklenir; `web_suspension`
 * kullanıcıyı askıya alır ve süresi dolunca zamanlayıcı geri alır.
 */
final class ManageSanctions
{
    public function __construct(private readonly AuditLogger $audit, private readonly GameCommandQueue $commands) {}

    /** @param  array<string, mixed>  $data  SanctionRequest::validated() */
    public function issue(User $actor, array $data): Sanction
    {
        $type = (string) $data['type'];

        abort_if($type === 'perm_ban' && ! $actor->hasPermission('sanctions.ban_permanent'), 403);

        $character = isset($data['character']) ? Character::query()->where('ulid', $data['character'])->firstOrFail() : null;
        $account = isset($data['account']) ? MtaAccount::query()->where('ulid', $data['account'])->firstOrFail() : $character?->account;
        $user = isset($data['user']) ? User::query()->where('username', $data['user'])->orWhere('ulid', $data['user'])->firstOrFail() : null;

        // Oyun hesabı verilmediyse kullanıcının tek aktif bağlı hesabı kullanılır.
        if ($account === null && $user !== null && $type !== 'web_suspension') {
            $links = GameAccountLink::query()->where('user_id', $user->id)->whereIn('status', GameAccountLink::ACTIVE)->with('account')->get();
            $account = $links->count() === 1 ? $links->first()?->account : null;
        }
        $user ??= $account?->activeLink?->user;

        if ($type === 'web_suspension' && $user === null) {
            throw ValidationException::withMessages(['user' => __('moderation.errors.user_required')]);
        }
        if ($type !== 'web_suspension' && $account === null) {
            throw ValidationException::withMessages(['account' => __('moderation.errors.account_required')]);
        }
        if ($user !== null && ($user->id === $actor->id || ($user->isStaff() && ! $actor->outranks($user)))) {
            throw ValidationException::withMessages(['user' => __('moderation.errors.hierarchy')]);
        }

        $minutes = in_array($type, Sanction::TIMED, true) ? (int) ($data['duration_minutes'] ?? 0) : null;
        if ($minutes !== null && $minutes < 1) {
            throw ValidationException::withMessages(['duration_minutes' => __('moderation.errors.duration_required')]);
        }

        $now = CarbonImmutable::now();
        $sanction = DB::transaction(function () use ($actor, $data, $type, $user, $account, $character, $minutes, $now): Sanction {
            $sanction = Sanction::query()->create([
                'user_id' => $user?->id,
                'mta_account_id' => $account?->id,
                'character_id' => $character?->id,
                'type' => $type,
                'public_reason' => (string) $data['public_reason'],
                'internal_note' => $data['internal_note'] ?? null,
                'evidence_url' => $data['evidence_url'] ?? null,
                'rule_id' => $data['rule_id'] ?? null,
                'starts_at' => $now,
                'ends_at' => $minutes !== null ? $now->addMinutes($minutes) : null,
                'status' => 'active',
                'appealable' => (bool) ($data['appealable'] ?? true),
                'issued_by' => $actor->id,
            ]);

            // web_suspension için kullanıcı yukarıda zorunlu tutuldu.
            if ($type === 'web_suspension') {
                $user->forceFill(['status' => UserStatus::Suspended, 'suspended_until' => $sanction->ends_at, 'suspension_reason' => mb_substr($sanction->public_reason, 0, 500)])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            if ($account !== null && isset(Sanction::GAME_COMMANDS[$type])) {
                $payload = ['account_ref' => $account->mta_account_ref];
                $payload += match ($type) {
                    'warning' => ['message' => $sanction->public_reason],
                    'kick' => ['reason' => $sanction->public_reason],
                    'perm_ban' => ['minutes' => null, 'reason' => $sanction->public_reason],
                    default => ['minutes' => $minutes, 'reason' => $sanction->public_reason],
                };
                $this->commands->enqueue($account->mta_server_id, Sanction::GAME_COMMANDS[$type], $payload, 'sanction:'.$sanction->ulid.':apply', $account, $actor, 'sanction', $sanction->id);
            }

            return $sanction;
        });

        $this->audit->record(AuditAction::SanctionsCreated, $sanction, [
            'after' => ['type' => $type, 'user_id' => $user?->id, 'mta_account_id' => $account?->id, 'ends_at' => $sanction->ends_at?->toIso8601String()],
        ], $sanction->public_reason, $actor);

        $user?->notify(new PanelNotice('sanction_created', __('moderation.notice.created', ['type' => __('moderation.types.'.$type)]), route('panel.sanctions.show', $sanction)));

        return $sanction;
    }

    public function revoke(User $actor, Sanction $sanction, string $reason): void
    {
        if ($sanction->status !== 'active') {
            throw ValidationException::withMessages(['reason' => __('moderation.errors.not_active')]);
        }

        DB::transaction(function () use ($actor, $sanction, $reason): void {
            $sanction->forceFill(['status' => 'revoked', 'revoked_by' => $actor->id, 'revoked_at' => now(), 'revoke_reason' => mb_substr($reason, 0, 500)])->save();
            $this->undo($sanction, $actor);
        });

        $this->audit->record(AuditAction::SanctionsRevoked, $sanction, ['before' => ['status' => 'active'], 'after' => ['status' => 'revoked']], $reason, $actor);
        $sanction->user?->notify(new PanelNotice('sanction_revoked', __('moderation.notice.revoked'), route('panel.sanctions.show', $sanction)));
    }

    /** Zamanlayıcı: süresi dolan aktif cezalar `expired` olur; askı süresi biten kullanıcı yeniden aktifleşir (FR-MOD-006). */
    public function expireDue(): int
    {
        $count = 0;
        foreach (Sanction::query()->where('status', 'active')->whereNotNull('ends_at')->where('ends_at', '<=', now())->get() as $sanction) {
            DB::transaction(function () use ($sanction): void {
                $sanction->forceFill(['status' => 'expired'])->save();
                if ($sanction->type === 'web_suspension') {
                    $this->restoreUser($sanction);
                }
            });
            $count++;
        }

        return $count;
    }

    /** FR-MOD-005 — oyuncu itirazı (ceza başına 1). */
    public function appeal(User $user, Sanction $sanction, string $body): SanctionAppeal
    {
        if (! $sanction->canBeAppealed()) {
            throw ValidationException::withMessages(['body' => __('moderation.errors.not_appealable')]);
        }

        try {
            return SanctionAppeal::query()->create(['sanction_id' => $sanction->id, 'user_id' => $user->id, 'body' => $body, 'status' => 'submitted']);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['body' => __('moderation.errors.already_appealed')]);
        }
    }

    /** FR-MOD-005 — cezayı veren kişi karar veremez. Kabul cezayı kaldırır. */
    public function decideAppeal(User $actor, SanctionAppeal $appeal, bool $accepted, string $publicNote, ?string $internalNote): void
    {
        $sanction = $appeal->sanction;

        if ($sanction->issued_by === $actor->id) {
            throw ValidationException::withMessages(['decision' => __('moderation.errors.own_sanction')]);
        }
        if (! in_array($appeal->status, ['submitted', 'in_review'], true)) {
            throw ValidationException::withMessages(['decision' => __('moderation.errors.appeal_decided')]);
        }

        DB::transaction(function () use ($actor, $appeal, $sanction, $accepted, $publicNote, $internalNote): void {
            $appeal->forceFill([
                'status' => $accepted ? 'accepted' : 'rejected', 'reviewed_by' => $actor->id, 'decided_at' => now(),
                'decision_note_public' => mb_substr($publicNote, 0, 1000), 'internal_note' => $internalNote,
            ])->save();

            if ($accepted && $sanction->status === 'active') {
                $sanction->forceFill(['status' => 'revoked', 'revoked_by' => $actor->id, 'revoked_at' => now(), 'revoke_reason' => 'İtiraz kabul edildi'])->save();
                $this->undo($sanction, $actor);
            }
        });

        $this->audit->record(AuditAction::SanctionsAppealDecided, $appeal, ['after' => ['status' => $appeal->status, 'sanction' => $sanction->ulid]], $publicNote, $actor);
        $appeal->user->notify(new PanelNotice('appeal_decided', __('moderation.notice.appeal_'.($accepted ? 'accepted' : 'rejected')), route('panel.sanctions.show', $sanction)));
    }

    private function undo(Sanction $sanction, User $actor): void
    {
        if ($sanction->type === 'web_suspension') {
            $this->restoreUser($sanction);
        }

        if (in_array($sanction->type, ['temp_ban', 'perm_ban'], true) && $sanction->account !== null) {
            $this->commands->enqueue($sanction->account->mta_server_id, 'player.unban', ['account_ref' => $sanction->account->mta_account_ref],
                'sanction:'.$sanction->ulid.':revoke', $sanction->account, $actor, 'sanction', $sanction->id);
        }
    }

    private function restoreUser(Sanction $sanction): void
    {
        $user = $sanction->user;
        $stillSuspended = Sanction::query()->where('user_id', $sanction->user_id)->where('type', 'web_suspension')
            ->where('status', 'active')->where('id', '!=', $sanction->id)->exists();

        if ($user !== null && ! $stillSuspended && $user->status === UserStatus::Suspended) {
            $user->forceFill(['status' => UserStatus::Active, 'suspended_until' => null, 'suspension_reason' => null])->save();
        }
    }
}
