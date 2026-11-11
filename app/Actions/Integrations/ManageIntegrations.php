<?php

declare(strict_types=1);

namespace App\Actions\Integrations;

use App\Enums\AuditAction;
use App\Models\IntegrationCredential;
use App\Models\MtaServer;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Bridge\ServerStatusResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * MTA sunucuları ve köprü kimlik bilgileri — docs/ROUTES.md §6.11, SRS FR-BRG-004, ADMIN §6.
 * Secret yalnızca oluşturma yanıtında bir kez döner; sunucu başına en fazla 2 aktif anahtar (rotasyon).
 */
final class ManageIntegrations
{
    public const MAX_ACTIVE_KEYS = 2;

    public function __construct(private readonly AuditLogger $audit, private readonly ServerStatusResolver $resolver) {}

    /** @param  array<string, mixed>  $data  MtaServerRequest::validated() */
    public function saveServer(User $actor, ?MtaServer $server, array $data, string $reason): MtaServer
    {
        $before = $server?->only(array_keys($data)) ?? [];
        $maintenanceChanged = $server !== null && array_key_exists('is_maintenance', $data) && (bool) $data['is_maintenance'] !== $server->is_maintenance;
        $server ??= new MtaServer;

        DB::transaction(function () use ($server, $data): void {
            if (($data['is_primary'] ?? false) === true) {
                MtaServer::query()->where('id', '!=', (int) $server->getKey())->update(['is_primary' => false]);
            }

            $server->fill($data)->save();
        });

        $this->resolver->forget($server->slug);
        $this->audit->record(
            $maintenanceChanged ? AuditAction::ServersMaintenanceToggled : AuditAction::IntegrationsServerUpdated,
            $server,
            ['before' => $before, 'after' => $server->only(array_keys($data))],
            $reason,
            $actor,
        );

        return $server;
    }

    /**
     * @param  list<string>  $scopes
     * @return array{credential: IntegrationCredential, secret: string}
     */
    public function issueCredential(User $actor, MtaServer $server, array $scopes, ?CarbonImmutable $expiresAt, string $reason): array
    {
        $secret = bin2hex(random_bytes(32));

        $credential = DB::transaction(function () use ($actor, $server, $scopes, $expiresAt, $secret): IntegrationCredential {
            $active = IntegrationCredential::query()->where('mta_server_id', $server->id)->whereNull('revoked_at')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->lockForUpdate()->count();

            if ($active >= self::MAX_ACTIVE_KEYS) {
                throw ValidationException::withMessages(['scopes' => __('game.errors.max_keys', ['max' => self::MAX_ACTIVE_KEYS])]);
            }

            $credential = new IntegrationCredential([
                'mta_server_id' => $server->id,
                'key_id' => 'mrp_live_'.Str::lower(Str::random(16)),
                'secret' => $secret,
                'scopes' => array_values(array_intersect(IntegrationCredential::SCOPES, $scopes)),
                'expires_at' => $expiresAt,
                'created_by' => $actor->id,
            ]);
            $credential->setAttribute('created_at', now());
            $credential->save();

            return $credential;
        });

        $this->audit->record(AuditAction::IntegrationsCredentialCreated, $credential, [
            'after' => ['key_id' => $credential->key_id, 'server' => $server->slug, 'scopes' => $credential->scopes],
        ], $reason, $actor);

        return ['credential' => $credential, 'secret' => $secret];
    }

    public function revokeCredential(User $actor, IntegrationCredential $credential, string $reason): void
    {
        if ($credential->revoked_at !== null) {
            return;
        }

        $credential->forceFill(['revoked_at' => now()])->save();

        $this->audit->record(AuditAction::IntegrationsCredentialRevoked, $credential, ['after' => ['key_id' => $credential->key_id]], $reason, $actor);
    }
}
