<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Ceza — docs/DATABASE.md §11.1, SRS FR-MOD-001..006. `internal_note`, `evidence_url` ve `issued_by` oyuncuya gösterilmez
 * (`$hidden` + panel görünümünde ayrı alan listesi).
 *
 * @property int $id
 * @property string $ulid
 * @property int|null $user_id
 * @property int|null $mta_account_id
 * @property int|null $character_id
 * @property string $type
 * @property string $public_reason
 * @property string|null $internal_note
 * @property string|null $evidence_url
 * @property int|null $rule_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property string $status
 * @property bool $appealable
 * @property int $issued_by
 * @property CarbonImmutable|null $revoked_at
 * @property string|null $revoke_reason
 * @property CarbonImmutable|null $created_at
 */
final class Sanction extends Model
{
    use HasPublicUlid;

    /** @var list<string> */
    public const TYPES = ['warning', 'mute', 'jail', 'kick', 'temp_ban', 'perm_ban', 'web_suspension'];

    /** @var list<string> Süre isteyen türler. */
    public const TIMED = ['mute', 'jail', 'temp_ban', 'web_suspension'];

    /** @var array<string, string> FR-MOD-003: oyun içi etkili tür → köprü komutu (ARCHITECTURE §7.5). */
    public const GAME_COMMANDS = [
        'warning' => 'player.warn', 'mute' => 'player.mute', 'jail' => 'player.jail', 'kick' => 'player.kick',
        'temp_ban' => 'player.ban', 'perm_ban' => 'player.ban',
    ];

    protected $fillable = [
        'user_id', 'mta_account_id', 'character_id', 'type', 'public_reason', 'internal_note', 'evidence_url', 'rule_id',
        'starts_at', 'ends_at', 'status', 'appealable', 'issued_by',
    ];

    protected $hidden = ['internal_note', 'evidence_url', 'issued_by', 'revoked_by'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime', 'appealable' => 'boolean',
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

    /** @return BelongsTo<Character, $this> */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /** @return BelongsTo<Rule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }

    /** @return BelongsTo<User, $this> */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** @return HasOne<SanctionAppeal, $this> */
    public function appeal(): HasOne
    {
        return $this->hasOne(SanctionAppeal::class);
    }

    /**
     * FR-MOD-004: oyuncunun kendisine veya aktif bağlı oyun hesaplarına verilen cezalar.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('user_id', $user->id)->orWhereIn('mta_account_id', GameAccountLink::query()
            ->where('user_id', $user->id)->whereIn('status', GameAccountLink::ACTIVE)->select('mta_account_id')));
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /** FR-MOD-005: ceza başına 1 itiraz, `appealable` ve ceza bitmeden önce. */
    public function canBeAppealed(): bool
    {
        return $this->appealable && $this->status === 'active' && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}
