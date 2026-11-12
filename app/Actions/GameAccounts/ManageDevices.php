<?php

declare(strict_types=1);

namespace App\Actions\GameAccounts;

use App\Actions\Bridge\GameEventLedger;
use App\Enums\AuditAction;
use App\Models\GameAccountLink;
use App\Models\GameLinkCode;
use App\Models\MtaAccount;
use App\Models\MtaAccountDevice;
use App\Models\MtaServer;
use App\Models\User;
use App\Notifications\PanelNotice;
use App\Services\Audit\AuditLogger;
use App\Services\Bridge\BridgeException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Cihaz/serial ilişkilendirme — docs/SRS.md FR-ACC-010, ARCHITECTURE §7.6, OD-07.
 * Ham serial yalnızca istek gövdesinde gelir; `HMAC(serial, BRIDGE_SERIAL_PEPPER)` hesaplanır, ham değer hiçbir tabloya,
 * loga (`RedactSensitiveData` "serial" anahtarını maskeler), olay defterine veya yanıta yazılmaz.
 */
final class ManageDevices
{
    public function __construct(private readonly GameEventLedger $ledger, private readonly AuditLogger $audit) {}

    public static function serialHmac(string $serial): string
    {
        $pepper = (string) config('roleplay.bridge.serial_pepper');

        if (strlen($pepper) < 16) {
            throw new BridgeException(503, 'SERVICE_UNAVAILABLE', 'Cihaz doğrulaması yapılandırılmamış.');
        }

        return hash_hmac('sha256', strtolower($serial), $pepper);
    }

    /**
     * `POST /api/v1/game/device-link/verify` — oyun içi `/cihazbagla KOD`.
     *
     * @param  array<string, mixed>  $data  DeviceLinkVerifyRequest::validated()
     * @return array{status: string, duplicate?: bool}
     */
    public function verify(MtaServer $server, array $data): array
    {
        $ref = (string) $data['account']['ref'];
        $limiterKey = 'device-verify:'.$server->id.':'.$ref;

        if (RateLimiter::tooManyAttempts($limiterKey, (int) config('roleplay.link_code.max_attempts'))) {
            throw new BridgeException(429, 'RATE_LIMITED', 'Çok fazla deneme. Biraz sonra tekrar dene.');
        }

        $serialHmac = self::serialHmac((string) $data['serial']);
        $code = LinkCodes::normalize((string) $data['code']);
        $hash = LinkCodes::isWellFormed($code) ? LinkCodes::hash($code) : null;

        try {
            return DB::transaction(function () use ($server, $data, $ref, $hash, $serialHmac): array {
                $eventId = $this->ledger->claim($server, (string) $data['event_id'], 'device_link.verify', null, null, ['account_ref' => $ref]);

                if ($eventId === null) {
                    return ['status' => 'pending_approval', 'duplicate' => true];
                }

                $record = $hash === null ? null : GameLinkCode::query()->where('code_hash', $hash)
                    ->where('purpose', LinkCodes::DEVICE_PURPOSE)->lockForUpdate()->first();

                $account = MtaAccount::query()->where('mta_server_id', $server->id)->where('mta_account_ref', $ref)->first();
                $owns = $record !== null && $account !== null && GameAccountLink::query()->where('user_id', $record->user_id)
                    ->where('mta_account_id', $account->id)->whereIn('status', GameAccountLink::ACTIVE)->exists();

                if ($record === null || $account === null || ! $record->isPending() || ! $owns) {
                    throw new BridgeException(422, 'LINK_CODE_INVALID', 'Kod geçersiz veya süresi dolmuş.');
                }

                $device = MtaAccountDevice::query()->where('mta_account_id', $account->id)->where('serial_hmac', $serialHmac)->lockForUpdate()->first();

                if ($device === null) {
                    $device = MtaAccountDevice::query()->create([
                        'mta_account_id' => $account->id, 'serial_hmac' => $serialHmac, 'status' => 'pending_approval',
                        'link_code_id' => $record->id, 'label' => 'Cihaz '.(MtaAccountDevice::query()->where('mta_account_id', $account->id)->count() + 1),
                    ]);
                } elseif ($device->status === 'revoked') {
                    $device->forceFill(['status' => 'pending_approval', 'link_code_id' => $record->id, 'revoked_at' => null, 'approved_at' => null, 'approved_by' => null])->save();
                }

                $record->forceFill(['used_at' => now(), 'used_by_mta_account_id' => $account->id])->save();
                $this->ledger->finish($eventId, 'processed', $account->id);

                return ['status' => $device->status];
            });
        } catch (BridgeException $e) {
            RateLimiter::hit($limiterKey, 600);

            throw $e;
        }
    }

    public function approve(User $actor, MtaAccountDevice $device, string $reason): void
    {
        $this->transition($actor, $device, 'active', AuditAction::DevicesApproved, $reason, ['pending_approval']);
    }

    public function reject(User $actor, MtaAccountDevice $device, string $reason): void
    {
        $this->transition($actor, $device, 'revoked', AuditAction::DevicesRejected, $reason, ['pending_approval']);
    }

    /** Oyuncu kendi cihazını kaldırır (yalnızca aktif bağlantısı olan hesabın cihazı; değilse 404). */
    public function revokeByOwner(User $user, MtaAccountDevice $device): void
    {
        $owns = GameAccountLink::query()->where('user_id', $user->id)->where('mta_account_id', $device->mta_account_id)
            ->whereIn('status', GameAccountLink::ACTIVE)->exists();

        abort_unless($owns, 404);

        $this->transition($user, $device, 'revoked', AuditAction::DevicesRevoked, 'Oyuncu tarafından kaldırıldı', ['pending_approval', 'active'], notify: false);
    }

    /** @param  list<string>  $from */
    private function transition(User $actor, MtaAccountDevice $device, string $to, AuditAction $action, string $reason, array $from, bool $notify = true): void
    {
        if (! in_array($device->status, $from, true)) {
            throw ValidationException::withMessages(['reason' => __('game.errors.state')]);
        }

        $before = $device->status;
        $device->forceFill($to === 'active'
            ? ['status' => 'active', 'approved_by' => $actor->id, 'approved_at' => now()]
            : ['status' => 'revoked', 'revoked_at' => now()])->save();

        $this->audit->record($action, $device, ['before' => ['status' => $before], 'after' => ['status' => $to]], $reason, $actor);

        if ($notify) {
            $owner = GameAccountLink::query()->where('mta_account_id', $device->mta_account_id)->whereIn('status', GameAccountLink::ACTIVE)->first()?->user;
            $owner?->notify(new PanelNotice('device_'.$to, $to === 'active' ? __('game.device.approved_notice') : __('game.device.rejected_notice'), route('panel.devices.index')));
        }
    }
}
