<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Tek kullanımlık bağlama kodu — docs/DATABASE.md §5.3, SRS FR-ACC-001..003. Kodun kendisi saklanmaz (HMAC).
 *
 * @property int $id
 * @property int $user_id
 * @property string $purpose
 * @property string $code_hash
 * @property int $attempts
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $used_at
 * @property int|null $used_by_mta_account_id
 * @property CarbonImmutable|null $cancelled_at
 */
final class GameLinkCode extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime', 'used_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime', 'attempts' => 'integer',
        ];
    }

    public function isPending(): bool
    {
        return $this->used_at === null && $this->cancelled_at === null && $this->expires_at->isFuture();
    }
}
