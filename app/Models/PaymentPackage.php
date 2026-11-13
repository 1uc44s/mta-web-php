<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kredi paketi — docs/DATABASE.md §6.4. Ödeme tutarı **her zaman** buradan alınır (FR-PAY-002).
 *
 * @property int $id
 * @property int $wallet_type_id
 * @property string $name
 * @property int $credit_amount
 * @property int $bonus_amount
 * @property int $price_minor
 * @property string $currency
 * @property bool $is_active
 */
final class PaymentPackage extends Model
{
    protected $fillable = ['wallet_type_id', 'name', 'credit_amount', 'bonus_amount', 'price_minor', 'currency', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['credit_amount' => 'integer', 'bonus_amount' => 'integer', 'price_minor' => 'integer', 'is_active' => 'boolean'];
    }

    /** @return BelongsTo<WalletType, $this> */
    public function walletType(): BelongsTo
    {
        return $this->belongsTo(WalletType::class);
    }

    public function totalCredit(): int
    {
        return $this->credit_amount + $this->bonus_amount;
    }
}
