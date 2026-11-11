<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** docs/DATABASE.md §3.8 (append-only, 180 gün saklama). */
final class UserSecurityEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'event', 'identifier_hash', 'ip_hash', 'ip_masked', 'user_agent_summary', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
