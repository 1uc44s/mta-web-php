<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Web Push aboneliği — docs/DATABASE.md §3.11a. `auth_token` şifreli saklanır; endpoint tekilliği `endpoint_hash` ile.
 *
 * @property int $id
 * @property int $user_id
 * @property string $endpoint
 * @property string $endpoint_hash
 * @property string $public_key
 * @property string $auth_token
 * @property string $content_encoding
 * @property string|null $user_agent_summary
 * @property int $failures
 * @property CarbonImmutable|null $last_used_at
 */
final class PushSubscription extends Model
{
    protected $fillable = ['user_id', 'endpoint', 'endpoint_hash', 'public_key', 'auth_token', 'content_encoding', 'user_agent_summary', 'failures', 'last_used_at'];

    protected $hidden = ['auth_token', 'endpoint'];

    protected function casts(): array
    {
        return ['auth_token' => 'encrypted', 'failures' => 'integer', 'last_used_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
