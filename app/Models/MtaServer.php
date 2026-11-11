<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * docs/DATABASE.md §4.2
 *
 * @property string $slug
 * @property string $name
 * @property string $connect_host
 * @property int $connect_port
 * @property int|null $max_players
 * @property int|null $last_player_count
 * @property CarbonImmutable|null $last_heartbeat_at
 * @property bool $is_maintenance
 * @property string|null $maintenance_note
 */
final class MtaServer extends Model
{
    protected $fillable = [
        'slug', 'name', 'connect_host', 'connect_port', 'max_players', 'game_mode', 'map_name',
        'last_heartbeat_at', 'last_player_count', 'resource_version', 'is_maintenance',
        'maintenance_note', 'is_primary', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'connect_port' => 'integer',
            'max_players' => 'integer',
            'last_player_count' => 'integer',
            'last_heartbeat_at' => 'immutable_datetime',
            'is_maintenance' => 'boolean',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderByDesc('is_primary')->orderBy('id');
    }

    public function connectAddress(): string
    {
        return $this->connect_host.':'.$this->connect_port;
    }

    public function connectUri(): string
    {
        return 'mtasa://'.$this->connectAddress();
    }
}
