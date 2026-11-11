<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Oyun komut kuyruğu (pull) — docs/DATABASE.md §5.7, ARCHITECTURE §7.5. Faz 7'de yalnızca `account.link_revoked`
 * kuyruğa alınır; pending/result uçları ve diğer komutlar Faz 8'de.
 *
 * @property int $id
 * @property string $ulid
 * @property int $mta_server_id
 * @property string $type
 * @property array<string, mixed> $payload
 * @property string $status
 * @property int $attempts
 * @property CarbonImmutable $expires_at
 */
final class GameCommand extends Model
{
    use HasPublicUlid;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array', 'result' => 'array', 'dispatched_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime',
        ];
    }
}
