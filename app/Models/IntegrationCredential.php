<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Köprü kimlik bilgisi — docs/DATABASE.md §4.3, SRS FR-BRG-004. `secret` şifreli saklanır, yalnızca oluşturulduğunda gösterilir.
 *
 * @property int $id
 * @property int $mta_server_id
 * @property string $key_id
 * @property string $secret
 * @property list<string> $scopes
 * @property CarbonImmutable|null $last_used_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $revoked_at
 * @property CarbonImmutable|null $created_at
 */
final class IntegrationCredential extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    public const SCOPES = ['heartbeat', 'events', 'account_link', 'commands'];

    protected $fillable = ['mta_server_id', 'key_id', 'secret', 'scopes', 'expires_at', 'revoked_at', 'created_by'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'scopes' => 'array',
            'last_used_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<MtaServer, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(MtaServer::class, 'mta_server_id');
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function allows(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
