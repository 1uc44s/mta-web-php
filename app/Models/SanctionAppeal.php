<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ceza itirazı — docs/DATABASE.md §11.2, SRS FR-MOD-005.
 *
 * @property int $id
 * @property string $ulid
 * @property int $sanction_id
 * @property int $user_id
 * @property string $body
 * @property string $status
 * @property int|null $reviewed_by
 * @property string|null $decision_note_public
 * @property string|null $internal_note
 * @property CarbonImmutable|null $decided_at
 * @property CarbonImmutable|null $created_at
 */
final class SanctionAppeal extends Model
{
    use HasPublicUlid;

    protected $fillable = ['sanction_id', 'user_id', 'body', 'status', 'reviewed_by', 'decision_note_public', 'internal_note', 'decided_at'];

    protected $hidden = ['internal_note', 'reviewed_by'];

    protected function casts(): array
    {
        return ['decided_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Sanction, $this> */
    public function sanction(): BelongsTo
    {
        return $this->belongsTo(Sanction::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
