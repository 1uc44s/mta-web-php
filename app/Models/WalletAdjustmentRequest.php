<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Manuel düzeltme / ters kayıt talebi — docs/DATABASE.md §6.8, SRS FR-WAL-006 (dört göz).
 *
 * @property int $id
 * @property string $ulid
 * @property int $wallet_id
 * @property string $direction
 * @property int $amount
 * @property string $reason
 * @property string $status
 * @property bool $requires_second_approval
 * @property int|null $reversal_of_id
 * @property int $requested_by
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $review_note
 * @property int|null $wallet_transaction_id
 * @property CarbonImmutable|null $created_at
 */
final class WalletAdjustmentRequest extends Model
{
    use HasPublicUlid;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer', 'requires_second_approval' => 'boolean',
            'reviewed_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Wallet, $this> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<WalletTransaction, $this> */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'reversal_of_id');
    }
}
