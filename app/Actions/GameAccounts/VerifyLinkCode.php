<?php

declare(strict_types=1);

namespace App\Actions\GameAccounts;

use App\Actions\Bridge\GameEventLedger;
use App\Enums\AuditAction;
use App\Models\GameAccountLink;
use App\Models\GameLinkCode;
use App\Models\MtaAccount;
use App\Models\MtaServer;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Bridge\BridgeException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * `POST /api/v1/game/account-link/verify` — docs/ARCHITECTURE.md §7.4, SRS FR-ACC-003..005.
 * Tüm adımlar tek transaction'da; kod satırı FOR UPDATE ile kilitlenir. Hata yanıtları sebep ayrıntısı içermez.
 * Yanlış denemeler hesap başına 5/10 dk ile sınırlanır; eşleşen kodun deneme sayısı 5'e ulaşırsa kod iptal edilir.
 */
final class VerifyLinkCode
{
    public function __construct(
        private readonly GameEventLedger $ledger,
        private readonly LinkCodes $codes,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data  AccountLinkVerifyRequest::validated()
     * @return array{linked: bool, web_username: string, duplicate?: bool}
     */
    public function handle(MtaServer $server, array $data): array
    {
        $ref = (string) $data['account']['ref'];
        $name = (string) $data['account']['name'];
        $code = LinkCodes::normalize((string) $data['code']);
        $limiterKey = 'link-verify:'.$server->id.':'.$ref;

        if (RateLimiter::tooManyAttempts($limiterKey, (int) config('roleplay.link_code.max_attempts'))) {
            throw new BridgeException(429, 'RATE_LIMITED', 'Çok fazla deneme. Biraz sonra tekrar dene.');
        }

        $hash = LinkCodes::isWellFormed($code) ? LinkCodes::hash($code) : null;

        try {
            $result = DB::transaction(function () use ($server, $data, $ref, $name, $hash): array {
                $eventId = $this->ledger->claim($server, (string) $data['event_id'], 'account_link.verify', null, null, ['account_ref' => $ref]);

                if ($eventId === null) {
                    return $this->replayed($server, $ref);
                }

                $record = $hash === null ? null : GameLinkCode::query()
                    ->where('code_hash', $hash)->where('purpose', LinkCodes::PURPOSE)->lockForUpdate()->first();

                if ($record === null || ! $record->isPending()) {
                    throw new BridgeException(422, 'LINK_CODE_INVALID', 'Kod geçersiz veya süresi dolmuş.');
                }

                $account = MtaAccount::query()->firstOrCreate(
                    ['mta_server_id' => $server->id, 'mta_account_ref' => $ref],
                    ['account_name' => $name, 'last_seen_at' => now()],
                );
                $account->forceFill(['account_name' => $name, 'last_seen_at' => now()])->save();

                $existing = GameAccountLink::query()->where('mta_account_id', $account->id)
                    ->whereIn('status', GameAccountLink::ACTIVE)->lockForUpdate()->first();

                if ($existing !== null && $existing->user_id !== $record->user_id) {
                    throw new BridgeException(409, 'ACCOUNT_ALREADY_LINKED', 'Bu oyun hesabı başka bir web hesabına bağlı.');
                }

                $user = User::query()->whereKey($record->user_id)->lockForUpdate()->firstOrFail();
                $link = $existing;

                if ($link === null) {
                    if ($this->codes->activeLinkCount($user) >= $this->codes->maxLinks()) {
                        throw new BridgeException(409, 'LINK_LIMIT_REACHED', 'Web hesabı en fazla bağlantı sayısına ulaştı.');
                    }

                    try {
                        $link = GameAccountLink::query()->create([
                            'user_id' => $user->id,
                            'mta_account_id' => $account->id,
                            'status' => 'active',
                            'verification_method' => 'ingame_code',
                            'linked_at' => now(),
                        ]);
                    } catch (UniqueConstraintViolationException) {
                        throw new BridgeException(409, 'ACCOUNT_ALREADY_LINKED', 'Bu oyun hesabı başka bir web hesabına bağlı.');
                    }
                }

                $record->forceFill(['used_at' => now(), 'used_by_mta_account_id' => $account->id])->save();
                $this->ledger->finish($eventId, 'processed', $account->id);

                return ['linked' => true, 'web_username' => $user->username, 'link' => $link, 'user' => $user, 'new' => $existing === null];
            });
        } catch (BridgeException $e) {
            RateLimiter::hit($limiterKey, 600);
            $this->countAttempt($hash);

            throw $e;
        }

        RateLimiter::clear($limiterKey);

        if (($result['new'] ?? false) === true) {
            $this->audit->record(AuditAction::AccountsLinked, $result['link'], [
                'after' => ['user_id' => $result['user']->id, 'mta_account_ref' => $ref, 'server' => $server->slug, 'method' => 'ingame_code'],
            ], null, null, 'bridge');
        }

        return array_intersect_key($result, array_flip(['linked', 'web_username', 'duplicate']));
    }

    /** @return array{linked: bool, web_username: string, duplicate: bool} */
    private function replayed(MtaServer $server, string $ref): array
    {
        $link = GameAccountLink::query()->whereIn('status', GameAccountLink::ACTIVE)
            ->whereHas('account', fn ($q) => $q->where('mta_server_id', $server->id)->where('mta_account_ref', $ref))
            ->with('user:id,username')->first();

        if ($link === null) {
            throw new BridgeException(422, 'LINK_CODE_INVALID', 'Kod geçersiz veya süresi dolmuş.');
        }

        return ['linked' => true, 'web_username' => (string) $link->user?->username, 'duplicate' => true];
    }

    private function countAttempt(?string $hash): void
    {
        if ($hash === null) {
            return;
        }

        $max = (int) config('roleplay.link_code.max_attempts');

        GameLinkCode::query()->where('code_hash', $hash)->whereNull('used_at')->whereNull('cancelled_at')->increment('attempts');
        GameLinkCode::query()->where('code_hash', $hash)->whereNull('used_at')->whereNull('cancelled_at')
            ->where('attempts', '>=', $max)->update(['cancelled_at' => now()]);
    }
}
