<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Oyun hesabı — docs/DATABASE.md §5.1. Yalnızca web'e bağlanan (veya bağlanmaya çalışılan) hesaplar kaydedilir.
 *
 * @property int $id
 * @property string $ulid
 * @property int $mta_server_id
 * @property string $mta_account_ref
 * @property string $account_name
 * @property CarbonImmutable|null $last_seen_at
 * @property CarbonImmutable|null $synced_at
 */
final class MtaAccount extends Model
{
    use HasPublicUlid;

    protected $fillable = ['mta_server_id', 'mta_account_ref', 'account_name', 'last_seen_at', 'synced_at'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'immutable_datetime', 'synced_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<MtaServer, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(MtaServer::class, 'mta_server_id');
    }

    /** @return HasMany<GameAccountLink, $this> */
    public function links(): HasMany
    {
        return $this->hasMany(GameAccountLink::class);
    }

    /** @return HasOne<GameAccountLink, $this> */
    public function activeLink(): HasOne
    {
        return $this->hasOne(GameAccountLink::class)->whereIn('status', GameAccountLink::ACTIVE);
    }

    /** @return HasMany<Character, $this> */
    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }
}
