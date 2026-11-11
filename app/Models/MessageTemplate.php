<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mesaj şablonu — docs/DATABASE.md §10.18, CMS §6. `variables` kod kaynaklıdır (seed) ve ACP'den değiştirilemez.
 *
 * @property int $id
 * @property string $key
 * @property string $channel
 * @property string $locale
 * @property string $name
 * @property string|null $subject
 * @property string $body
 * @property list<string> $variables
 * @property string $status
 * @property bool $is_system
 * @property int $version
 * @property int|null $updated_by
 */
final class MessageTemplate extends Model
{
    protected $fillable = ['key', 'channel', 'locale', 'name', 'subject', 'body', 'variables', 'status', 'is_system', 'version', 'updated_by'];

    protected function casts(): array
    {
        return ['variables' => 'array', 'is_system' => 'boolean', 'version' => 'integer'];
    }

    /** @return HasMany<MessageTemplateRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(MessageTemplateRevision::class)->orderByDesc('version');
    }
}
