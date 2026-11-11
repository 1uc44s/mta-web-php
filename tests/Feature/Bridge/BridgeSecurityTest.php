<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Models\Character;
use App\Models\GameAccountLink;
use App\Models\MtaAccount;
use App\Models\User;
use App\Services\Bridge\BridgeAlerts;
use App\Services\Bridge\BridgeSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('FR-BRG-002: ortak test vektörleri PHP imzalayıcısıyla birebir eşleşir (Lua ile aynı dosya)', function (): void {
    $vectors = json_decode((string) file_get_contents(base_path('mta-resource/web_bridge/tests/vectors.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach ($vectors['hmac'] as $v) {
        expect(hash_hmac('sha256', $v['data'], (string) hex2bin($v['key_hex'])))->toBe($v['hmac'], $v['name']);
    }

    foreach ($vectors['canonical'] as $v) {
        $canonical = BridgeSigner::canonical($v['method'], $v['path'], $v['timestamp'], $v['nonce'], $v['body']);
        expect(hash('sha256', $v['body']))->toBe($v['body_sha256'])
            ->and(BridgeSigner::sign($v['secret'], $canonical))->toBe($v['signature'], $v['name']);
    }
});

it('FR-BRG-001: geçerli imzalı ping 200 döner ve sunucu bağlamını içerir', function (): void {
    $bridge = bridgeCredential();

    bridgeCall($bridge, 'GET', '/api/v1/game/ping')
        ->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.server', 'main')->assertJsonStructure(['requestId']);

    expect($bridge['credential']->fresh()->last_used_at)->not->toBeNull();
});

it('FR-BRG-001/002: eksik başlık, yanlış imza ve bilinmeyen anahtar 401 SIGNATURE_INVALID', function (array $override): void {
    $bridge = bridgeCredential();

    bridgeCall($bridge, 'GET', '/api/v1/game/ping', null, $override)
        ->assertStatus(401)->assertJsonPath('error.code', 'SIGNATURE_INVALID');
})->with([
    'yanlış imza' => [['signature' => str_repeat('a', 64)]],
    'bilinmeyen anahtar' => [['key' => 'mrp_unknown_key01']],
    'bozuk nonce' => [['nonce' => 'xyz']],
    'eksik anahtar' => [['key' => '']],
]);

it('FR-BRG-003: ±300 sn dışındaki zaman damgası reddedilir', function (): void {
    $bridge = bridgeCredential();

    bridgeCall($bridge, 'GET', '/api/v1/game/ping', null, ['timestamp' => (string) (time() - 301)])
        ->assertStatus(401)->assertJsonPath('error.code', 'SIGNATURE_INVALID');
});

it('FR-BRG-003: aynı nonce ikinci kez 409 REPLAY_DETECTED', function (): void {
    $bridge = bridgeCredential();
    $nonce = bin2hex(random_bytes(32));

    bridgeCall($bridge, 'GET', '/api/v1/game/ping', null, ['nonce' => $nonce])->assertOk();
    bridgeCall($bridge, 'GET', '/api/v1/game/ping', null, ['nonce' => $nonce])
        ->assertStatus(409)->assertJsonPath('error.code', 'REPLAY_DETECTED');
});

it('FR-BRG-003: imzası geçersiz istek nonce tablosuna yazılmaz', function (): void {
    $bridge = bridgeCredential();

    bridgeCall($bridge, 'GET', '/api/v1/game/ping', null, ['signature' => str_repeat('b', 64)])->assertStatus(401);

    expect(DB::table('bridge_nonces')->count())->toBe(0);
});

it('FR-BRG-004: iptal edilmiş veya süresi dolmuş anahtar reddedilir', function (string $column): void {
    $bridge = bridgeCredential();
    $bridge['credential']->forceFill([$column => now()->subMinute()])->save();

    bridgeCall($bridge, 'GET', '/api/v1/game/ping')->assertStatus(401);
})->with(['revoked_at', 'expires_at']);

it('FR-BRG-004: kapsamı olmayan anahtar uca erişemez (403)', function (): void {
    $bridge = bridgeCredential(['heartbeat']);

    bridgeCall($bridge, 'POST', '/api/v1/game/accounts/sync', ['event_id' => (string) Str::uuid(), 'accounts' => [['ref' => '1', 'name' => 'a']]])
        ->assertStatus(403)->assertJsonPath('error.code', 'FORBIDDEN');
});

it('FR-BRG-005: 64 KB üzerindeki gövde 413 döner', function (): void {
    $bridge = bridgeCredential();

    bridgeCall($bridge, 'POST', '/api/v1/game/heartbeat', ['players' => 1, 'max_players' => 10, 'server_name' => str_repeat('x', 70 * 1024)])
        ->assertStatus(413);
});

it('FR-BRG-005: şemaya uymayan gövde VALIDATION_ERROR döner', function (): void {
    $bridge = bridgeCredential();

    bridgeCall($bridge, 'POST', '/api/v1/game/heartbeat', ['players' => 'çok'])
        ->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');
});

it('FR-BRG-009: heartbeat kaydı ve sunucu özet alanları güncellenir', function (): void {
    $bridge = bridgeCredential();

    bridgeCall($bridge, 'POST', '/api/v1/game/heartbeat', [
        'players' => 87, 'max_players' => 300, 'server_name' => 'Meridyen RP', 'game_mode' => 'Roleplay',
        'map' => 'San Andreas', 'resource_version' => '1.0.0', 'uptime_sec' => 86400, 'bilinmeyen' => 'yok sayılır',
    ])->assertOk()->assertJsonPath('data.status', 'online')->assertJsonPath('data.maintenance', false);

    $server = $bridge['server']->fresh();
    expect($server->last_player_count)->toBe(87)
        ->and($server->max_players)->toBe(300)
        ->and($server->last_heartbeat_at)->not->toBeNull()
        ->and(DB::table('server_heartbeats')->count())->toBe(1)
        ->and((string) DB::table('server_heartbeats')->value('payload'))->not->toContain('bilinmeyen');

    $this->getJson('/api/v1/public/server-status')->assertJsonPath('data.status', 'online');
});

it('FR-BRG-007: aynı event_id ikinci kez duplicate:true döner ve yan etki üretmez', function (): void {
    $bridge = bridgeCredential();
    $account = MtaAccount::query()->create(['mta_server_id' => $bridge['server']->id, 'mta_account_ref' => '1042', 'account_name' => 'kaan']);
    $body = ['event_id' => (string) Str::uuid(), 'occurred_at' => now()->toIso8601String(), 'account' => ['ref' => '1042', 'name' => 'kaan2']];

    bridgeCall($bridge, 'POST', '/api/v1/game/player/connected', $body)->assertOk()->assertJsonPath('data.duplicate', false);
    bridgeCall($bridge, 'POST', '/api/v1/game/player/connected', $body)->assertOk()->assertJsonPath('data.duplicate', true);

    expect(DB::table('game_events')->count())->toBe(1)
        ->and($account->fresh()->account_name)->toBe('kaan2');
});

it('ROUTES §7.2: bağlı olmayan hesaplar senkronda oluşturulmaz (veri minimizasyonu)', function (): void {
    $bridge = bridgeCredential();

    bridgeCall($bridge, 'POST', '/api/v1/game/player/connected', [
        'event_id' => (string) Str::uuid(), 'occurred_at' => now()->toIso8601String(), 'account' => ['ref' => '9999', 'name' => 'yabanci'],
    ])->assertOk();
    bridgeCall($bridge, 'POST', '/api/v1/game/accounts/sync', [
        'event_id' => (string) Str::uuid(), 'accounts' => [['ref' => '9998', 'name' => 'baska']],
    ])->assertOk()->assertJsonPath('data.updated', 0);

    expect(MtaAccount::query()->count())->toBe(0);
});

it('FR-ACC-008/009: karakter senkronu yalnızca bağlı hesapta çalışır ve profil allowlist uygular', function (): void {
    $bridge = bridgeCredential();
    $account = MtaAccount::query()->create(['mta_server_id' => $bridge['server']->id, 'mta_account_ref' => '1042', 'account_name' => 'kaan']);
    $payload = fn (): array => ['event_id' => (string) Str::uuid(), 'account_ref' => '1042', 'characters' => [[
        'ref' => 'c-551', 'name' => 'Kaan Demir', 'status' => 'active', 'level' => 12, 'playtime_minutes' => 5400,
        'public_profile' => ['bio' => '<b>Merhaba</b>', 'phone' => '555-0000'],
    ]]];

    bridgeCall($bridge, 'POST', '/api/v1/game/characters/sync', $payload())->assertOk()->assertJsonPath('data.ignored', true);
    expect(Character::query()->count())->toBe(0);

    GameAccountLink::query()->create(['user_id' => User::factory()->create()->id, 'mta_account_id' => $account->id, 'status' => 'active', 'verification_method' => 'ingame_code', 'linked_at' => now()]);

    bridgeCall($bridge, 'POST', '/api/v1/game/characters/sync', $payload())->assertOk()->assertJsonPath('data.synced', 1);

    $character = Character::query()->firstOrFail();
    expect($character->name)->toBe('Kaan Demir')
        ->and($character->public_profile)->toBe(['bio' => 'Merhaba']);
});

it('FR-BRG-008: eşik aşılınca tek kritik audit kaydı oluşur', function (): void {
    $alerts = app(BridgeAlerts::class);

    for ($i = 0; $i < BridgeAlerts::THRESHOLD + 5; $i++) {
        $alerts->failure('signature_invalid', ['key_id' => null]);
    }

    expect(DB::table('audit_logs')->where('action', AuditAction::BridgeSignatureAlert->value)->count())->toBe(1);
});

it('FR-BRG-012: köprü yanıtları e-posta veya IP içermez', function (): void {
    $bridge = bridgeCredential();

    $content = (string) bridgeCall($bridge, 'GET', '/api/v1/game/ping')->getContent();

    expect($content)->not->toContain('@')->not->toContain('127.0.0.1');
});
