<?php

declare(strict_types=1);

namespace App\Actions\Bridge;

use App\Models\MtaServer;
use App\Services\Bridge\ServerStatusResolver;
use App\Services\Settings\SettingsRepository;
use Illuminate\Support\Facades\DB;

/** Heartbeat — docs/SRS.md FR-BRG-009: `server_heartbeats` insert + `mta_servers` özet alanları + durum cache'i temizlenir. */
final class RecordHeartbeat
{
    public function __construct(private readonly ServerStatusResolver $resolver, private readonly SettingsRepository $settings) {}

    /**
     * @param  array<string, mixed>  $data  HeartbeatRequest::validated()
     * @return array{status: string, maintenance: bool}
     */
    public function handle(MtaServer $server, array $data): array
    {
        $now = now();
        $max = (int) $data['max_players'];
        $players = min((int) $data['players'], $max);

        DB::transaction(function () use ($server, $data, $players, $max, $now): void {
            DB::table('server_heartbeats')->insert([
                'mta_server_id' => $server->id,
                'player_count' => $players,
                'max_players' => $max,
                'payload' => json_encode([
                    'server_name' => $data['server_name'] ?? null,
                    'game_mode' => $data['game_mode'] ?? null,
                    'map' => $data['map'] ?? null,
                    'resource_version' => $data['resource_version'] ?? null,
                    'uptime_sec' => $data['uptime_sec'] ?? null,
                ], JSON_THROW_ON_ERROR),
                'received_at' => $now,
            ]);

            $server->forceFill([
                'last_heartbeat_at' => $now,
                'last_player_count' => $players,
                'max_players' => $max,
                'game_mode' => $data['game_mode'] ?? $server->getAttribute('game_mode'),
                'map_name' => $data['map'] ?? $server->getAttribute('map_name'),
                'resource_version' => $data['resource_version'] ?? $server->getAttribute('resource_version'),
            ])->save();
        });

        $this->resolver->forget($server->slug);
        $this->resolver->forget((string) $this->settings->get('server.primary_slug', 'main'));

        return ['status' => $this->resolver->resolve($server)->status->value, 'maintenance' => $server->is_maintenance];
    }
}
