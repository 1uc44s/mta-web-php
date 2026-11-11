<?php

declare(strict_types=1);

namespace App\Actions\Bridge;

use App\Models\MtaServer;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Olay idempotency defteri — docs/SRS.md FR-BRG-007, DATABASE §5.6. `(mta_server_id, event_id)` unique olduğundan
 * tekrar gelen olay `null` döner ve çağıran yan etki üretmeden `{duplicate:true}` yanıtlar.
 * Payload'a yalnızca minimal, kişisel olmayan alanlar yazılır (kod, serial, IP asla).
 */
final class GameEventLedger
{
    /** @param  array<string, mixed>|null  $payload */
    public function claim(MtaServer $server, string $eventId, string $type, ?CarbonImmutable $occurredAt, ?int $mtaAccountId = null, ?array $payload = null): ?int
    {
        try {
            return (int) DB::table('game_events')->insertGetId([
                'mta_server_id' => $server->id,
                'event_id' => strtolower($eventId),
                'type' => $type,
                'mta_account_id' => $mtaAccountId,
                'payload' => $payload !== null ? json_encode($payload, JSON_THROW_ON_ERROR) : null,
                'status' => 'received',
                'occurred_at' => $occurredAt ?? now(),
                'received_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    public function finish(int $eventId, string $status = 'processed', ?int $mtaAccountId = null, ?string $error = null): void
    {
        DB::table('game_events')->where('id', $eventId)->update(array_filter([
            'status' => $status,
            'mta_account_id' => $mtaAccountId,
            'error' => $error !== null ? mb_substr($error, 0, 500) : null,
            'processed_at' => now(),
        ], fn (mixed $value): bool => $value !== null));
    }
}
