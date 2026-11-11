<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kural revizyonu — docs/DATABASE.md §10.12 (her kural değişikliği bir kayıt oluşturur).
 *
 * @property int $id
 * @property int $rule_id
 * @property string $title
 * @property string $body
 * @property int|null $changed_by
 * @property string|null $change_note
 * @property CarbonImmutable $created_at
 */
final class RuleRevision extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
