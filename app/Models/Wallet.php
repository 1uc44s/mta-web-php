<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cüzdan — docs/DATABASE.md §6.2, SRS FR-WAL-001/002. `balance` ve `version` toplu atamaya kapalıdır; yalnızca
 * `App\Services\Wallet\LedgerService` sorgu düzeyinde yazar.
 *
 * @property int $id
 * @property int $user_id
 * @property int $wallet_type_id
 * @property int $balance
 * @property int $version
 * @property string $status
 */
final class Wallet extends Model
{
    protected $fillable = ['user_id', 'wallet_type_id', 'status'];

    protected function casts(): array
    {
        return ['balance' => 'integer', 'version' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<WalletType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(WalletType::class, 'wallet_type_id');
    }

    /** @return HasMany<WalletTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
