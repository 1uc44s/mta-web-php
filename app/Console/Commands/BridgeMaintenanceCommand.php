<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Köprü bakımı — docs/SRS.md FR-BRG-003 (nonce 15 dk), DATABASE §4.4 (heartbeat 30 gün), §4.5 (günlük istatistik),
 * §5.3 (kullanılmış/süresi dolmuş bağlama kodları 7 gün), §5.6 (game_events 90 gün), §5.7 (süresi dolan komutlar).
 *   --stats: dünün `server_stats_daily` satırını hesaplar (günlük 00:10).
 */
#[Signature('bridge:maintain {--stats : Dünün sunucu istatistiklerini hesapla}')]
#[Description('Köprü nonce/heartbeat/olay temizliği ve günlük sunucu istatistikleri')]
final class BridgeMaintenanceCommand extends Command
{
    public function handle(): int
    {
        if ($this->option('stats')) {
            return $this->aggregate(CarbonImmutable::yesterday());
        }

        $nonces = DB::table('bridge_nonces')->where('created_at', '<', now()->subMinutes((int) config('roleplay.bridge.nonce_ttl_minutes')))->delete();
        $heartbeats = DB::table('server_heartbeats')->where('received_at', '<', now()->subDays(30))->delete();
        $events = DB::table('game_events')->where('received_at', '<', now()->subDays(90))->delete();
        $codes = DB::table('game_link_codes')->where('expires_at', '<', now()->subDays(7))->delete();
        $commands = DB::table('game_commands')->whereIn('status', ['pending', 'dispatched'])->where('expires_at', '<', now())
            ->update(['status' => 'expired', 'updated_at' => now()]);

        $this->info("nonce: {$nonces}, heartbeat: {$heartbeats}, olay: {$events}, kod: {$codes}, süresi dolan komut: {$commands}");

        return self::SUCCESS;
    }

    private function aggregate(CarbonImmutable $day): int
    {
        $from = $day->startOfDay();
        $to = $day->endOfDay();
        $expected = 24 * 60 * 2; // 30 sn aralıklı heartbeat beklenir (ARCHITECTURE §7.8)

        $rows = DB::table('server_heartbeats')->whereBetween('received_at', [$from, $to])
            ->groupBy('mta_server_id')
            ->selectRaw('mta_server_id, MAX(player_count) AS peak, AVG(player_count) AS avg_players, COUNT(*) AS beats')
            ->get();

        foreach ($rows as $row) {
            $unique = DB::table('game_events')->where('mta_server_id', $row->mta_server_id)->where('type', 'player.connected')
                ->whereBetween('occurred_at', [$from, $to])->whereNotNull('mta_account_id')->distinct()->count('mta_account_id');

            DB::table('server_stats_daily')->upsert([[
                'mta_server_id' => $row->mta_server_id,
                'date' => $day->toDateString(),
                'peak_players' => (int) $row->peak,
                'avg_players' => round((float) $row->avg_players, 2),
                'uptime_ratio' => min(1, round((int) $row->beats / $expected, 4)),
                'unique_accounts' => $unique,
            ]], ['mta_server_id', 'date'], ['peak_players', 'avg_players', 'uptime_ratio', 'unique_accounts']);
        }

        $this->info("{$day->toDateString()}: {$rows->count()} sunucu");

        return self::SUCCESS;
    }
}
