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
 * Destek talebi — docs/DATABASE.md §8.2, SRS FR-SUP-001..011.
 *
 * @property int $id
 * @property string $ulid
 * @property string $number
 * @property int $user_id
 * @property int $category_id
 * @property int $branch_id
 * @property string $subject
 * @property string $status
 * @property string $priority
 * @property int|null $assigned_to
 * @property string $idempotency_key
 * @property CarbonImmutable|null $last_message_at
 * @property CarbonImmutable|null $last_player_message_at
 * @property CarbonImmutable|null $last_staff_message_at
 * @property CarbonImmutable|null $first_staff_response_at
 * @property bool $player_unread
 * @property bool $staff_unread
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $reopen_requested_at
 * @property CarbonImmutable|null $created_at
 */
final class SupportTicket extends Model
{
    use HasPublicUlid;

    /** @var list<string> FR-SUP-005 durumları */
    public const STATUSES = ['open', 'in_progress', 'waiting_for_player', 'waiting_for_staff', 'resolved', 'closed'];

    /** @var list<string> */
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    /** @var list<string> Açık sayılan durumlar */
    public const ACTIVE = ['open', 'in_progress', 'waiting_for_player', 'waiting_for_staff'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'immutable_datetime', 'last_player_message_at' => 'immutable_datetime',
            'last_staff_message_at' => 'immutable_datetime', 'first_staff_response_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime', 'closed_at' => 'immutable_datetime', 'reopen_requested_at' => 'immutable_datetime',
            'player_unread' => 'boolean', 'staff_unread' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<SupportCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SupportCategory::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<SupportMessage, $this> Tüm mesajlar (iç notlar dahil) — yalnızca personel ekranlarında. */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id')->orderBy('created_at')->orderBy('id');
    }

    /** @return HasMany<SupportMessage, $this> Oyuncuya açık mesajlar (FR-SUP-006 katman 1). */
    public function publicMessages(): HasMany
    {
        return $this->messages()->where('visibility', SupportMessage::PUBLIC);
    }

    /** @return HasMany<SupportTicketEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(SupportTicketEvent::class, 'ticket_id')->orderBy('created_at')->orderBy('id');
    }

    /** @return HasMany<SupportAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportAttachment::class, 'ticket_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE, true);
    }

    /**
     * Personel kuyruğu kapsamı (FR-SUP-007, ADMIN §5.2): branş üyeliği veya support.all_branches.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('support.all_branches')) {
            return $query;
        }

        return $query->whereIn('branch_id', $user->activeBranchIds());
    }
}
