<?php

declare(strict_types=1);

namespace App\Actions\Bridge;

use App\Models\MtaAccount;
use App\Models\MtaServer;
use Carbon\CarbonImmutable;

/**
 * Oyuncu giriş/çıkış ve toplu hesap senkronu — docs/ROUTES.md §7.2. Veri minimizasyonu: web'de kaydı olmayan hesaplar
 * oluşturulmaz, yalnızca mevcut `mta_accounts` güncellenir.
 */
final class SyncPresence
{
    public function __construct(private readonly GameEventLedger $ledger) {}

    /**
     * @param  array<string, mixed>  $data  PlayerEventRequest::validated()
     * @return array{duplicate: bool}
     */
    public function player(MtaServer $server, string $type, array $data): array
    {
        $occurredAt = CarbonImmutable::parse((string) $data['occurred_at']);
        $account = MtaAccount::query()->where('mta_server_id', $server->id)->where('mta_account_ref', (string) $data['account']['ref'])->first();

        $eventId = $this->ledger->claim($server, (string) $data['event_id'], $type, $occurredAt, $account?->id,
            $type === 'player.disconnected' ? ['session_seconds' => $data['session_seconds'] ?? null] : null);

        if ($eventId === null) {
            return ['duplicate' => true];
        }

        if ($account !== null && ($account->last_seen_at === null || $account->last_seen_at->lt($occurredAt))) {
            $account->forceFill(['last_seen_at' => $occurredAt, 'account_name' => (string) $data['account']['name']])->save();
        }

        $this->ledger->finish($eventId, $account !== null ? 'processed' : 'ignored');

        return ['duplicate' => false];
    }

    /**
     * @param  array<string, mixed>  $data  AccountsSyncRequest::validated()
     * @return array{duplicate: bool, updated?: int}
     */
    public function accounts(MtaServer $server, array $data): array
    {
        /** @var list<array{ref: string, name: string, last_seen_at?: string|null}> $rows */
        $rows = $data['accounts'];
        $eventId = $this->ledger->claim($server, (string) $data['event_id'], 'accounts.sync', null, null, ['count' => count($rows)]);

        if ($eventId === null) {
            return ['duplicate' => true];
        }

        $byRef = collect($rows)->keyBy('ref');
        $updated = 0;

        foreach (MtaAccount::query()->where('mta_server_id', $server->id)->whereIn('mta_account_ref', $byRef->keys()->all())->get() as $account) {
            $row = $byRef->get($account->mta_account_ref);
            $account->forceFill([
                'account_name' => $row['name'],
                'last_seen_at' => isset($row['last_seen_at']) ? CarbonImmutable::parse($row['last_seen_at']) : $account->last_seen_at,
                'synced_at' => now(),
            ])->save();
            $updated++;
        }

        $this->ledger->finish($eventId);

        return ['duplicate' => false, 'updated' => $updated];
    }
}
