<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Oyun karakteri (salt okunur senkron kopya) — docs/DATABASE.md §5.4, SRS FR-ACC-008/009.
 *
 * @property int $id
 * @property string $ulid
 * @property int $mta_server_id
 * @property int $mta_account_id
 * @property string $mta_character_ref
 * @property string $name
 * @property string $status
 * @property int|null $faction_id
 * @property int|null $game_job_id
 * @property int|null $level
 * @property int|null $playtime_minutes
 * @property array<string, mixed>|null $public_profile
 * @property bool $is_profile_hidden
 * @property CarbonImmutable|null $last_seen_at
 * @property CarbonImmutable $synced_at
 */
final class Character extends Model
{
    use HasPublicUlid;

    protected $fillable = [
        'mta_server_id', 'mta_account_id', 'mta_character_ref', 'name', 'status', 'faction_id', 'game_job_id', 'level',
        'playtime_minutes', 'public_profile', 'is_profile_hidden', 'last_seen_at', 'created_in_game_at', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'public_profile' => 'array', 'is_profile_hidden' => 'boolean', 'last_seen_at' => 'immutable_datetime',
            'created_in_game_at' => 'immutable_datetime', 'synced_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<MtaAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MtaAccount::class, 'mta_account_id');
    }

    /** @return BelongsTo<Faction, $this> */
    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class);
    }

    /** @return BelongsTo<GameJob, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(GameJob::class, 'game_job_id');
    }

    /**
     * FR-ACC-008: yalnızca kullanıcının aktif bağlantılı hesaplarının karakterleri.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereIn('mta_account_id', GameAccountLink::query()
            ->where('user_id', $user->id)->whereIn('status', GameAccountLink::ACTIVE)->select('mta_account_id'));
    }

    /** @return array<string, mixed> FR-ACC-009: yalnızca allowlist'teki alanlar (gizliyse boş). */
    public function publicFields(): array
    {
        if ($this->is_profile_hidden) {
            return [];
        }

        return array_intersect_key($this->public_profile ?? [], array_flip((array) config('roleplay.character_public_fields')));
    }
}
