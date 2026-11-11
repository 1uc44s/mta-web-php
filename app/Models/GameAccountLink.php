<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Web ↔ oyun hesabı bağlantısı — docs/DATABASE.md §5.2, SRS FR-ACC-004..006.
 *
 * @property int $id
 * @property int $user_id
 * @property int $mta_account_id
 * @property string $status
 * @property string $verification_method
 * @property CarbonImmutable $linked_at
 * @property CarbonImmutable|null $unlink_requested_at
 * @property string|null $unlink_reason
 * @property CarbonImmutable|null $unlinked_at
 */
final class GameAccountLink extends Model
{
    /** @var list<string> Aktif sayılan durumlar (unique kısıtı bunlara uygulanır). */
    public const ACTIVE = ['active', 'unlink_requested'];

    protected $fillable = ['user_id', 'mta_account_id', 'status', 'verification_method', 'linked_at', 'unlink_requested_at', 'unlink_reason', 'unlinked_at', 'unlinked_by'];

    protected function casts(): array
    {
        return [
            'linked_at' => 'immutable_datetime', 'unlink_requested_at' => 'immutable_datetime', 'unlinked_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<MtaAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MtaAccount::class, 'mta_account_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE, true);
    }
}
