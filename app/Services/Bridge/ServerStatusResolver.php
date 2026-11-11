<?php

declare(strict_types=1);

namespace App\Services\Bridge;

use App\Enums\ServerStatus;
use App\Models\MtaServer;
use App\Services\Settings\SettingsRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Heartbeat yaşına göre dürüst sunucu durumu (docs/ARCHITECTURE.md §7.8, docs/SRS.md FR-BRG-010, US-PUB-03).
 * maintenance > online (< 90 sn) > stale (90 sn – 5 dk) > offline (> 5 dk veya hiç heartbeat yok).
 */
final class ServerStatusResolver
{
    public function __construct(private readonly SettingsRepository $settings) {}

    public function resolve(MtaServer $server, ?CarbonImmutable $now = null): ServerStatusSnapshot
    {
        $now ??= CarbonImmutable::now();
        $age = $server->last_heartbeat_at !== null
            ? max(0, (int) $server->last_heartbeat_at->diffInSeconds($now, absolute: true))
            : null;

        $status = match (true) {
            $server->is_maintenance => ServerStatus::Maintenance,
            $age === null => ServerStatus::Offline,
            $age < (int) config('roleplay.server_status.fresh_seconds') => ServerStatus::Online,
            $age <= (int) config('roleplay.server_status.offline_after_seconds') => ServerStatus::Stale,
            default => ServerStatus::Offline,
        };

        return new ServerStatusSnapshot(
            server: $server->slug,
            name: $server->name,
            status: $status,
            players: $this->settings->bool('server.show_player_count') ? $server->last_player_count : null,
            maxPlayers: $server->max_players,
            lastHeartbeatAt: $server->last_heartbeat_at,
            freshnessSeconds: $age,
            host: $server->connect_host,
            port: $server->connect_port,
            maintenanceNote: $server->maintenance_note,
        );
    }

    /** Public sayfalarda kullanılan birincil sunucu. Kısa süreli cache (NFR-PERF-008). */
    public function primary(): ?ServerStatusSnapshot
    {
        $slug = (string) $this->settings->get('server.primary_slug', 'main');

        // Laravel 13 cache'i nesne unserialize etmez; ham kolon dizisi saklanır ve modele geri yüklenir.
        $attributes = Cache::remember(
            'server-status:attrs:'.$slug,
            (int) config('roleplay.server_status.public_cache_seconds'),
            fn (): ?array => (MtaServer::query()->where('slug', $slug)->where('is_active', true)->first()
                ?? MtaServer::query()->primary()->first())?->getAttributes(),
        );

        return is_array($attributes) ? $this->resolve((new MtaServer)->newFromBuilder($attributes)) : null;
    }

    public function forget(string $slug): void
    {
        Cache::forget('server-status:attrs:'.$slug);
    }
}
