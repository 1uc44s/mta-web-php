<?php

declare(strict_types=1);

namespace App\Actions\Bridge;

use App\Enums\AuditAction;
use App\Models\GameCommand;
use App\Models\MtaAccount;
use App\Models\MtaServer;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Bridge\BridgeException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Oyun komut kuyruğu (pull) — docs/SRS.md FR-BRG-011, ARCHITECTURE §7.5.
 *   enqueue → pending (en fazla 20, `dispatched`, attempts++) → result (`succeeded|failed`)
 *   2 dk içinde sonuç gelmezse tekrar `pending` (en fazla 3 deneme), sonra `failed`.
 * Tür allowlist'i `config/roleplay.php bridge.commands` ile Lua `shared/constants.lua` birebir aynıdır.
 */
final class GameCommandQueue
{
    public const BATCH = 20;

    public function __construct(private readonly AuditLogger $audit) {}

    /** @param  array<string, mixed>  $payload */
    public function enqueue(
        MtaServer|int $server,
        string $type,
        array $payload,
        string $idempotencyKey,
        ?MtaAccount $target = null,
        ?User $requestedBy = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?CarbonImmutable $expiresAt = null,
    ): GameCommand {
        if (! array_key_exists($type, (array) config('roleplay.bridge.commands'))) {
            throw new InvalidArgumentException("İzin verilmeyen komut türü: {$type}");
        }

        return GameCommand::query()->firstOrCreate(['idempotency_key' => $idempotencyKey], [
            'mta_server_id' => $server instanceof MtaServer ? $server->id : $server,
            'type' => $type,
            'target_mta_account_id' => $target?->id,
            'payload' => $payload,
            'status' => 'pending',
            'requested_by' => $requestedBy?->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'expires_at' => $expiresAt ?? now()->addDay(),
        ]);
    }

    /** @return list<array{id: string, type: string, payload: array<string, mixed>, expires_at: string}> */
    public function pending(MtaServer $server): array
    {
        return DB::transaction(function () use ($server): array {
            $commands = GameCommand::query()->where('mta_server_id', $server->id)->where('status', 'pending')
                ->where('expires_at', '>', now())->orderBy('id')->limit(self::BATCH)->lockForUpdate()->get();

            foreach ($commands as $command) {
                $command->forceFill(['status' => 'dispatched', 'dispatched_at' => now(), 'attempts' => $command->attempts + 1])->save();
            }

            return $commands->map(fn (GameCommand $c): array => [
                'id' => $c->ulid, 'type' => $c->type, 'payload' => $c->payload, 'expires_at' => $c->expires_at->toIso8601String(),
            ])->values()->all();
        });
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array{id: string, status: string, duplicate: bool}
     */
    public function result(MtaServer $server, string $ulid, string $status, array $detail): array
    {
        $command = GameCommand::query()->where('mta_server_id', $server->id)->where('ulid', strtolower($ulid))->first();

        if ($command === null) {
            throw new BridgeException(404, 'NOT_FOUND', 'Komut bulunamadı.');
        }

        if (in_array($command->status, ['succeeded', 'failed', 'cancelled', 'expired'], true)) {
            return ['id' => $command->ulid, 'status' => $command->status, 'duplicate' => true];
        }

        $command->forceFill([
            'status' => $status,
            'result' => array_intersect_key($detail, array_flip(['applied_at', 'error', 'message'])),
            'completed_at' => now(),
        ])->save();

        if ($status === 'failed') {
            $this->audit->record(AuditAction::GameCommandsFailed, $command, ['after' => ['type' => $command->type, 'error' => $detail['error'] ?? null]], null, null, 'bridge');
        }

        return ['id' => $command->ulid, 'status' => $status, 'duplicate' => false];
    }

    /** Zamanlayıcı (her dakika): sonuçsuz kalan `dispatched` komutları yeniden kuyruğa al veya başarısız say. */
    public function requeueStale(): int
    {
        $timeout = (int) config('roleplay.bridge.command_result_timeout_seconds', 120);
        $max = (int) config('roleplay.bridge.command_max_attempts', 3);
        $count = 0;

        foreach (GameCommand::query()->where('status', 'dispatched')->where('dispatched_at', '<', now()->subSeconds($timeout))->get() as $command) {
            $failed = $command->attempts >= $max;
            $command->forceFill($failed
                ? ['status' => 'failed', 'result' => ['error' => 'timeout'], 'completed_at' => now()]
                : ['status' => 'pending'])->save();
            $count++;

            if ($failed) {
                $this->audit->record(AuditAction::GameCommandsFailed, $command, ['after' => ['type' => $command->type, 'error' => 'timeout']], null, null, 'scheduler');
            }
        }

        return $count;
    }

    public function cancel(GameCommand $command): bool
    {
        if ($command->status !== 'pending') {
            return false;
        }

        return $command->forceFill(['status' => 'cancelled', 'completed_at' => now()])->save();
    }
}
