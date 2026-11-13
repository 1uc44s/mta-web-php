<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * İade — docs/DATABASE.md §6.7, SRS FR-PAY-005. Onaylayan ≠ talep eden (servis kontrolü).
 *
 * @property int $id
 * @property string $ulid
 * @property int $payment_order_id
 * @property int $amount_minor
 * @property string $reason
 * @property string $status
 * @property string|null $provider_refund_id
 * @property int|null $wallet_transaction_id
 * @property int $requested_by
 * @property int|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $created_at
 */
final class Refund extends Model
{
    use HasPublicUlid;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer', 'approved_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<PaymentOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'payment_order_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
