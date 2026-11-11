<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sayfa revizyonu (snapshot) — docs/DATABASE.md §10.4.
 *
 * @property int $id
 * @property int $page_id
 * @property array<string, mixed> $snapshot
 * @property string|null $note
 * @property int|null $created_by
 * @property CarbonImmutable $created_at
 */
final class PageRevision extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
