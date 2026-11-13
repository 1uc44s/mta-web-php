<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mutabakat çalıştırması — docs/DATABASE.md §6.9, SRS FR-WAL-007.
 *
 * @property int $id
 * @property string $ulid
 * @property int|null $triggered_by
 * @property int $wallets_checked
 * @property int $mismatches
 * @property int $total_balance
 * @property int $ledger_net
 * @property list<array{wallet_id: int, balance: int, ledger_net: int}>|null $details
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $finished_at
 */
final class WalletReconciliationRun extends Model
{
    use HasPublicUlid;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'details' => 'array', 'wallets_checked' => 'integer', 'mismatches' => 'integer', 'total_balance' => 'integer',
            'ledger_net' => 'integer', 'started_at' => 'immutable_datetime', 'finished_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function trigger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
