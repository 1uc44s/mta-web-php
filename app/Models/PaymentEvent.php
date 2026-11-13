<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Webhook gelen kutusu — docs/DATABASE.md §6.6, SRS FR-PAY-003. `(provider, provider_event_id)` unique: aynı olay ikinci
 * kez işlenemez. Payload kişisel/kart verisinden temizlenmiş olarak saklanır.
 *
 * @property int $id
 * @property string $provider
 * @property string $provider_event_id
 * @property int|null $payment_order_id
 * @property string $event_type
 * @property bool $signature_valid
 * @property array<string, mixed> $payload
 * @property string $status
 * @property string|null $error
 * @property CarbonImmutable $received_at
 * @property CarbonImmutable|null $processed_at
 */
final class PaymentEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payload' => 'array', 'signature_valid' => 'boolean',
            'received_at' => 'immutable_datetime', 'processed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<PaymentOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'payment_order_id');
    }
}
