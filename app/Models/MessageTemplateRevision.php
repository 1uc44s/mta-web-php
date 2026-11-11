<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Şablonun önceki sürümü — docs/DATABASE.md §10.18.
 *
 * @property int $id
 * @property int $message_template_id
 * @property int $version
 * @property string|null $subject
 * @property string $body
 * @property int|null $changed_by
 * @property CarbonImmutable $created_at
 */
final class MessageTemplateRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['message_template_id', 'version', 'subject', 'body', 'changed_by'];

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
