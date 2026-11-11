<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only, hash zincirli denetim kaydı — docs/DATABASE.md §11.3. Yalnızca App\Services\Audit\AuditLogger yazar.
 *
 * @property int $id
 * @property string $ulid
 * @property string $actor_type
 * @property int|null $actor_id
 * @property string $action
 * @property string|null $target_type
 * @property int|null $target_id
 * @property string $severity
 * @property array<string, mixed>|null $changes
 * @property string|null $reason
 * @property string|null $ip_masked
 * @property string|null $user_agent_summary
 * @property string|null $request_id
 * @property string|null $prev_hash
 * @property string $hash
 * @property CarbonImmutable $created_at
 */
final class AuditLog extends Model
{
    use HasPublicUlid;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
