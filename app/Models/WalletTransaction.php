<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Ledger kaydı (append-only) — docs/DATABASE.md §6.3, SRS FR-WAL-003/004. UPDATE/DELETE DB trigger'ı ile engellidir;
 * düzeltme yalnızca `reversal_of_id` ile ters kayıttır.
 *
 * @property int $id
 * @property string $ulid
 * @property int $wallet_id
 * @property string $direction
 * @property int $amount
 * @property int $balance_before
 * @property int $balance_after
 * @property string $reason
 * @property string|null $source_type
 * @property int|null $source_id
 * @property int|null $reversal_of_id
 * @property string $idempotency_key
 * @property string $description
 * @property int|null $created_by
 * @property CarbonImmutable $created_at
 */
final class WalletTransaction extends Model
{
    use HasPublicUlid;

    public const UPDATED_AT = null;

    /** @var list<string> */
    public const REASONS = ['topup', 'purchase', 'admin_adjustment', 'refund', 'reversal', 'promo', 'game_sync'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer', 'balance_before' => 'integer', 'balance_after' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Wallet, $this> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /** @return HasOne<WalletTransaction, $this> */
    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    /** İşaretli tutar (+ credit, − debit) — mutabakat ve listeler için. */
    public function signedAmount(): int
    {
        return $this->direction === 'credit' ? $this->amount : -$this->amount;
    }
}
