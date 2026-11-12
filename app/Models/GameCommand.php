<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Oyun komut kuyruğu (pull) — docs/DATABASE.md §5.7, ARCHITECTURE §7.5. Faz 8: `GameCommandQueue`
 * (enqueue/pending/result/requeue); kaynaklar: cezalar (`source_type=sanction`), elle komut (`manual`), bağlantı iptali.
 *
 * @property int $id
 * @property string $ulid
 * @property int $mta_server_id
 * @property string $type
 * @property array<string, mixed> $payload
 * @property string $status
 * @property int $attempts
 * @property CarbonImmutable $expires_at
 * @property int|null $requested_by
 * @property array<string, mixed>|null $result
 * @property CarbonImmutable|null $dispatched_at
 * @property CarbonImmutable|null $completed_at
 */
final class GameCommand extends Model
{
    use HasPublicUlid;

    protected $guarded = ['id'];

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<MtaAccount, $this> */
    public function target(): BelongsTo
    {
        return $this->belongsTo(MtaAccount::class, 'target_mta_account_id');
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array', 'result' => 'array', 'dispatched_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime',
        ];
    }
}
