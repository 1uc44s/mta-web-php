<?php

declare(strict_types=1);

namespace App\Services\Bridge;

use App\Enums\ServerStatus;
use Carbon\CarbonImmutable;

/** Public sunucu durumu (docs/ROUTES.md §7.1). Değişmez değer nesnesi. */
final readonly class ServerStatusSnapshot
{
    public function __construct(
        public string $server,
        public string $name,
        public ServerStatus $status,
        public ?int $players,
        public ?int $maxPlayers,
        public ?CarbonImmutable $lastHeartbeatAt,
        public ?int $freshnessSeconds,
        public string $host,
        public int $port,
        public ?string $maintenanceNote = null,
    ) {}

    public function address(): string
    {
        return $this->host.':'.$this->port;
    }

    public function uri(): string
    {
        return 'mtasa://'.$this->address();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'server' => $this->server,
            'name' => $this->name,
            'status' => $this->status->value,
            'label' => $this->status->label(),
            'players' => $this->status->showsPlayers() ? $this->players : null,
            'max_players' => $this->maxPlayers,
            'last_heartbeat_at' => $this->lastHeartbeatAt?->toIso8601ZuluString(),
            'freshness_seconds' => $this->freshnessSeconds,
            'maintenance_note' => $this->status === ServerStatus::Maintenance ? $this->maintenanceNote : null,
            'connect' => ['host' => $this->host, 'port' => $this->port, 'uri' => $this->uri()],
        ];
    }
}
