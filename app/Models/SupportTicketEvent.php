<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ticket olay geçmişi — docs/DATABASE.md §8.5. `note` yalnızca personel görür.
 *
 * @property int $id
 * @property int $ticket_id
 * @property int|null $actor_id
 * @property string $type
 * @property string|null $from_value
 * @property string|null $to_value
 * @property string|null $note
 * @property CarbonImmutable $created_at
 */
final class SupportTicketEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['ticket_id', 'actor_id', 'type', 'from_value', 'to_value', 'note'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
