<?php

declare(strict_types=1);

namespace App\Actions\GameAccounts;

use App\Enums\AuditAction;
use App\Models\Character;
use App\Models\GameAccountLink;
use App\Models\GameCommand;
use App\Models\MtaAccount;
use App\Models\MtaServer;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bağlantı durum geçişleri — docs/SRS.md FR-ACC-005/006, ROUTES §5.3 / §6.6, DATABASE §5.2.
 *   active → unlink_requested (oyuncu) → unlinked (onay) | active (ret)
 *   active|unlink_requested → revoked (yetkili)  · admin_manual bağlantı (gerekçe + kanıt)
 * Bağlantı biten her durumda köprüye `account.link_revoked` komutu kuyruğa alınır.
 */
final class ManageLinks
{
    public function __construct(private readonly AuditLogger $audit, private readonly LinkCodes $codes) {}

    public function requestUnlink(User $user, GameAccountLink $link, string $reason): void
    {
        if ($link->user_id !== $user->id || $link->status !== 'active') {
            throw ValidationException::withMessages(['reason' => __('game.errors.unlink_state')]);
        }

        $link->forceFill(['status' => 'unlink_requested', 'unlink_requested_at' => now(), 'unlink_reason' => mb_substr($reason, 0, 500)])->save();

        $this->audit->record(AuditAction::AccountsUnlinkRequested, $link, ['after' => ['status' => 'unlink_requested']], $reason, $user);
    }

    public function approveUnlink(User $actor, GameAccountLink $link, string $reason): void
    {
        $this->end($actor, $link, 'unlinked', AuditAction::AccountsUnlinked, $reason, ['unlink_requested']);
    }

    public function revoke(User $actor, GameAccountLink $link, string $reason): void
    {
        $this->end($actor, $link, 'revoked', AuditAction::AccountsLinkRevoked, $reason, GameAccountLink::ACTIVE);
    }

    public function rejectUnlink(User $actor, GameAccountLink $link, string $reason): void
    {
        if ($link->status !== 'unlink_requested') {
            throw ValidationException::withMessages(['reason' => __('game.errors.state')]);
        }

        $link->forceFill(['status' => 'active', 'unlink_requested_at' => null])->save();

        $this->audit->record(AuditAction::AccountsUnlinkRejected, $link, ['before' => ['status' => 'unlink_requested'], 'after' => ['status' => 'active']], $reason, $actor);
    }

    /** Hesap silme/anonimleştirme (DATABASE §11 adım 3): kullanıcının tüm aktif bağlantıları sistem tarafından iptal edilir. */
    public function revokeAllFor(User $user): void
    {
        GameAccountLink::query()->where('user_id', $user->id)->whereIn('status', GameAccountLink::ACTIVE)->with('account')->get()
            ->each(function (GameAccountLink $link): void {
                $link->forceFill(['status' => 'revoked', 'unlinked_at' => now()])->save();
                $this->queueRevokedCommand($link, null);
            });
    }

    /** Brief §5.1 alternatif yöntem — yetkili, oyuncunun kimliğini başka kanıtla doğrulayıp manuel bağlar. */
    public function manualLink(User $actor, User $user, MtaServer $server, string $ref, string $name, string $reason, string $evidence): GameAccountLink
    {
        $link = DB::transaction(function () use ($user, $server, $ref, $name): GameAccountLink {
            $account = MtaAccount::query()->firstOrCreate(['mta_server_id' => $server->id, 'mta_account_ref' => $ref], ['account_name' => $name]);

            if (GameAccountLink::query()->where('mta_account_id', $account->id)->whereIn('status', GameAccountLink::ACTIVE)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['mta_account_ref' => __('game.errors.already_linked')]);
            }

            if ($this->codes->activeLinkCount($user) >= $this->codes->maxLinks()) {
                throw ValidationException::withMessages(['user' => __('game.errors.limit')]);
            }

            try {
                return GameAccountLink::query()->create([
                    'user_id' => $user->id, 'mta_account_id' => $account->id, 'status' => 'active',
                    'verification_method' => 'admin_manual', 'linked_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['mta_account_ref' => __('game.errors.already_linked')]);
            }
        });

        $this->audit->record(AuditAction::AccountsManualLinked, $link, [
            'after' => ['user_id' => $user->id, 'mta_account_ref' => $ref, 'server' => $server->slug, 'evidence' => mb_substr($evidence, 0, 1000)],
        ], $reason, $actor);

        return $link;
    }

    public function setCharacterVisibility(User $actor, Character $character, bool $hidden, string $reason): void
    {
        $before = $character->is_profile_hidden;
        $character->forceFill(['is_profile_hidden' => $hidden])->save();

        $this->audit->record(AuditAction::CharactersVisibilityChanged, $character, [
            'before' => ['is_profile_hidden' => $before], 'after' => ['is_profile_hidden' => $hidden],
        ], $reason, $actor);
    }

    /** @param  list<string>  $from */
    private function end(User $actor, GameAccountLink $link, string $status, AuditAction $action, string $reason, array $from): void
    {
        if (! in_array($link->status, $from, true)) {
            throw ValidationException::withMessages(['reason' => __('game.errors.state')]);
        }

        $before = $link->status;

        DB::transaction(function () use ($actor, $link, $status): void {
            $link->forceFill(['status' => $status, 'unlinked_at' => now(), 'unlinked_by' => $actor->id])->save();
            $this->queueRevokedCommand($link, $actor);
        });

        $this->audit->record($action, $link, ['before' => ['status' => $before], 'after' => ['status' => $status]], $reason, $actor);
    }

    /** ARCHITECTURE §7.5 — `account.link_revoked {account_ref}` (sistem komutu, idempotent). */
    private function queueRevokedCommand(GameAccountLink $link, ?User $actor): void
    {
        $account = $link->account;

        if ($account === null) {
            return;
        }

        GameCommand::query()->firstOrCreate(['idempotency_key' => 'link:'.$link->id.':revoked'], [
            'mta_server_id' => $account->mta_server_id,
            'type' => 'account.link_revoked',
            'target_mta_account_id' => $account->id,
            'payload' => ['account_ref' => $account->mta_account_ref],
            'status' => 'pending',
            'requested_by' => $actor?->id,
            'source_type' => 'game_account_link',
            'source_id' => $link->id,
            'expires_at' => now()->addDay(),
        ]);
    }
}
