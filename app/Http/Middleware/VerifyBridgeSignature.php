<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\IntegrationCredential;
use App\Services\Bridge\BridgeAlerts;
use App\Services\Bridge\BridgeSigner;
use App\Support\BridgeResponse;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Köprü isteği doğrulama — docs/ARCHITECTURE.md §7.2 (sıra bağlayıcıdır), SRS FR-BRG-001..005.
 *   1 başlıklar/biçim → 2 credential aktif → 3 zaman penceresi (±300 sn) → 4 gövde ≤ 64 KB →
 *   5 HMAC (hash_equals) → 6 nonce INSERT (imzadan SONRA; çakışma = replay 409) → 7 last_used_at.
 * Rota parametresi olarak kapsam (scope) verilir: `bridge:heartbeat`. Doğrulanan credential ve sunucu isteğe eklenir.
 */
final class VerifyBridgeSignature
{
    public function __construct(private readonly BridgeAlerts $alerts) {}

    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $keyId = (string) $request->header('X-Bridge-Key', '');
        $timestamp = (string) $request->header('X-Bridge-Timestamp', '');
        $nonce = strtolower((string) $request->header('X-Bridge-Nonce', ''));
        $signature = (string) $request->header('X-Bridge-Signature', '');

        // 1
        if (preg_match('/^[A-Za-z0-9_]{8,32}$/', $keyId) !== 1 || preg_match('/^\d{9,11}$/', $timestamp) !== 1
            || preg_match('/^[0-9a-f]{32,64}$/', $nonce) !== 1 || preg_match('/^[0-9a-fA-F]{64}$/', $signature) !== 1) {
            return $this->reject($request, 'signature_invalid', 401, 'SIGNATURE_INVALID');
        }

        // 2
        $credential = IntegrationCredential::query()->with('server')->where('key_id', $keyId)->first();
        if ($credential === null || ! $credential->isUsable() || ! $credential->server->is_active) {
            return $this->reject($request, 'signature_invalid', 401, 'SIGNATURE_INVALID', $keyId);
        }

        // 3
        if (abs(time() - (int) $timestamp) > (int) config('roleplay.bridge.timestamp_window_seconds', 300)) {
            return $this->reject($request, 'stale_timestamp', 401, 'SIGNATURE_INVALID', $keyId);
        }

        // 4
        $body = (string) $request->getContent();
        if (strlen($body) > (int) config('roleplay.bridge.max_body_kilobytes', 64) * 1024) {
            return $this->reject($request, 'payload_too_large', 413, 'PAYLOAD_TOO_LARGE', $keyId);
        }

        // 5
        $canonical = BridgeSigner::canonical($request->method(), '/'.ltrim($request->path(), '/'), $timestamp, $nonce, $body);
        if (! BridgeSigner::matches($credential->secret, $canonical, $signature)) {
            return $this->reject($request, 'signature_invalid', 401, 'SIGNATURE_INVALID', $keyId);
        }

        if ($scope !== null && ! $credential->allows($scope)) {
            return $this->reject($request, 'scope_denied', 403, 'FORBIDDEN', $keyId);
        }

        // 6
        try {
            DB::table('bridge_nonces')->insert(['nonce' => $nonce, 'credential_id' => $credential->id, 'created_at' => now()]);
        } catch (UniqueConstraintViolationException) {
            return $this->reject($request, 'replay', 409, 'REPLAY_DETECTED', $keyId);
        }

        // 7 — dakikada en fazla bir yazım
        if ($credential->last_used_at === null || $credential->last_used_at->lt(now()->subMinute())) {
            $credential->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        $request->attributes->set('bridge.credential', $credential);
        $request->attributes->set('bridge.server', $credential->server);

        return $next($request);
    }

    private function reject(Request $request, string $reason, int $status, string $code, ?string $keyId = null): Response
    {
        $this->alerts->failure($reason, ['key_id' => $keyId, 'path' => $request->path(), 'request_id' => $request->headers->get('X-Request-Id')]);

        return BridgeResponse::error($status, $code, match ($code) {
            'REPLAY_DETECTED' => 'İstek daha önce işlendi.',
            'PAYLOAD_TOO_LARGE' => 'İstek gövdesi çok büyük.',
            'FORBIDDEN' => 'Bu anahtar bu işlem için yetkili değil.',
            default => 'İmza doğrulanamadı.',
        });
    }
}
