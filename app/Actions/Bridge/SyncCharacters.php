<?php

declare(strict_types=1);

namespace App\Actions\Bridge;

use App\Models\Character;
use App\Models\Faction;
use App\Models\GameJob;
use App\Models\MtaAccount;
use App\Models\MtaServer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Karakter senkronu — docs/ROUTES.md §7.2, SRS FR-ACC-008/009. Yalnızca web'e **bağlı** hesaplar kabul edilir;
 * `public_profile` allowlist dışı anahtarları düşürülür; `faction_key`/`job_key` slug ile eşlenir.
 */
final class SyncCharacters
{
    /** @var list<string> */
    public const STATUSES = ['active', 'inactive', 'dead', 'banned', 'archived'];

    public function __construct(private readonly GameEventLedger $ledger) {}

    /**
     * @param  array<string, mixed>  $data  CharactersSyncRequest::validated()
     * @return array<string, bool|int>
     */
    public function handle(MtaServer $server, array $data): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = $data['characters'];
        $account = MtaAccount::query()->where('mta_server_id', $server->id)->where('mta_account_ref', (string) $data['account_ref'])
            ->whereHas('activeLink')->first();

        $eventId = $this->ledger->claim($server, (string) $data['event_id'], 'characters.sync', null, $account?->id, ['count' => count($rows)]);

        if ($eventId === null) {
            return ['duplicate' => true];
        }

        if ($account === null) {
            $this->ledger->finish($eventId, 'ignored');

            return ['duplicate' => false, 'ignored' => true];
        }

        $factions = Faction::query()->pluck('id', 'slug');
        $jobs = GameJob::query()->pluck('id', 'slug');
        $allowed = array_flip((array) config('roleplay.character_public_fields'));

        DB::transaction(function () use ($server, $account, $rows, $factions, $jobs, $allowed): void {
            foreach ($rows as $row) {
                $profile = array_map(
                    fn (mixed $value): mixed => is_string($value) ? mb_substr(strip_tags($value), 0, 500) : $value,
                    array_filter(array_intersect_key((array) ($row['public_profile'] ?? []), $allowed), is_scalar(...)),
                );
                $status = (string) ($row['status'] ?? 'active');

                Character::query()->updateOrCreate(
                    ['mta_server_id' => $server->id, 'mta_character_ref' => (string) $row['ref']],
                    [
                        'mta_account_id' => $account->id,
                        'name' => (string) $row['name'],
                        'status' => in_array($status, self::STATUSES, true) ? $status : 'active',
                        'level' => $row['level'] ?? null,
                        'playtime_minutes' => $row['playtime_minutes'] ?? null,
                        'faction_id' => isset($row['faction_key']) ? $factions->get($row['faction_key']) : null,
                        'game_job_id' => isset($row['job_key']) ? $jobs->get($row['job_key']) : null,
                        'public_profile' => $profile === [] ? null : $profile,
                        'created_in_game_at' => isset($row['created_at']) ? CarbonImmutable::parse((string) $row['created_at']) : null,
                        'last_seen_at' => isset($row['last_seen_at']) ? CarbonImmutable::parse((string) $row['last_seen_at']) : null,
                        'synced_at' => now(),
                    ],
                );
            }

            $account->forceFill(['synced_at' => now()])->save();
        });

        $this->ledger->finish($eventId);

        return ['duplicate' => false, 'ignored' => false, 'synced' => count($rows)];
    }
}
