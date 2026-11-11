<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Admin görüntüleme/işlem izi — docs/DATABASE.md §11.4 (90 gün saklama).
 *
 * @property int $id
 * @property int $user_id
 * @property string $route_name
 * @property string $method
 * @property string|null $target_type
 * @property int|null $target_id
 * @property string|null $ip_masked
 * @property CarbonImmutable $created_at
 */
final class AdminActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'route_name', 'method', 'target_type', 'target_id', 'ip_masked'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
