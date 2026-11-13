<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ödeme siparişi — docs/DATABASE.md §6.5, SRS FR-PAY-002..004. Tutar/para birimi/kredi sipariş anında paketten kopyalanır.
 *
 * @property int $id
 * @property string $ulid
 * @property string $order_number
 * @property int $user_id
 * @property int $wallet_id
 * @property int|null $payment_package_id
 * @property string $provider
 * @property string|null $provider_order_id
 * @property int $amount_minor
 * @property string $currency
 * @property int $credit_amount
 * @property string $status
 * @property int $refunded_minor
 * @property string|null $failure_reason
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $created_at
 */
final class PaymentOrder extends Model
{
    use HasPublicUlid;

    /** @var list<string> Ödemesi tamamlanmamış (bekleyen) durumlar. */
    public const PENDING = ['created', 'pending', 'requires_action'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer', 'credit_amount' => 'integer', 'refunded_minor' => 'integer',
            'expires_at' => 'immutable_datetime', 'paid_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Wallet, $this> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /** @return BelongsTo<PaymentPackage, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(PaymentPackage::class, 'payment_package_id');
    }

    /** @return HasMany<PaymentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    /** @return HasMany<Refund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, self::PENDING, true);
    }

    public function formattedAmount(): string
    {
        return number_format($this->amount_minor / 100, 2, ',', '.').' '.$this->currency;
    }
}
