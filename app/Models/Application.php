<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Başvuru — docs/DATABASE.md §9.2, SRS FR-APP-003..006.
 *
 * @property int $id
 * @property string $ulid
 * @property int $user_id
 * @property int $application_type_id
 * @property int|null $character_id
 * @property string $status
 * @property int|null $assigned_to
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $decided_at
 * @property int|null $decided_by
 * @property string|null $decision_note_public
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
final class Application extends Model
{
    use HasPublicUlid;

    /** @var list<string> Açık sayılan durumlar (uq_applications_open_key). */
    public const OPEN = ['draft', 'submitted', 'in_review', 'needs_info'];

    /** @var list<string> */
    public const STATUSES = ['draft', 'submitted', 'in_review', 'needs_info', 'approved', 'rejected', 'cancelled'];

    protected $fillable = ['user_id', 'application_type_id', 'character_id', 'status', 'assigned_to', 'submitted_at', 'decided_at', 'decided_by', 'decision_note_public'];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'immutable_datetime', 'decided_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime', 'updated_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<ApplicationType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ApplicationType::class, 'application_type_id');
    }

    /** @return BelongsTo<Character, $this> */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<ApplicationAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(ApplicationAnswer::class);
    }

    /** @return HasMany<ApplicationMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ApplicationMessage::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return HasMany<ApplicationMessage, $this> Oyuncu görünümü: iç mesajlar asla yüklenmez. */
    public function publicMessages(): HasMany
    {
        return $this->messages()->where('visibility', 'public');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    /**
     * FR-APP-005: `applications.all_branches` veya türün branşına üyelik.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeReviewableBy(Builder $query, User $user): Builder
    {
        if ($user->hasUnscopedPermission('applications.all_branches')) {
            return $query;
        }

        return $query->whereIn('application_type_id', ApplicationType::query()->whereIn('branch_id', $user->activeBranchIds())->select('id'));
    }
}
