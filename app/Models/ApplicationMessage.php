<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Başvuru mesajı — docs/DATABASE.md §9.4. `internal` mesajlar oyuncu sorgularına hiç girmez.
 *
 * @property int $id
 * @property int $application_id
 * @property int|null $author_id
 * @property string $visibility
 * @property string $body
 * @property CarbonImmutable|null $created_at
 */
final class ApplicationMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['application_id', 'author_id', 'visibility', 'body'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
