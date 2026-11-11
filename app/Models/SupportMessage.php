<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ticket mesajı — docs/DATABASE.md §8.3. `internal` mesajlar oyuncuya asla gösterilmez (FR-SUP-006).
 *
 * @property int $id
 * @property int $ticket_id
 * @property int|null $author_id
 * @property string $author_type
 * @property string $visibility
 * @property string $body
 * @property CarbonImmutable $created_at
 */
final class SupportMessage extends Model
{
    public const PUBLIC = 'public';

    public const INTERNAL = 'internal';

    public const UPDATED_AT = null;

    protected $fillable = ['ticket_id', 'author_id', 'author_type', 'visibility', 'body', 'edited_at'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime', 'edited_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return HasMany<SupportAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportAttachment::class, 'message_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', self::PUBLIC);
    }

    public function isInternal(): bool
    {
        return $this->visibility === self::INTERNAL;
    }
}
