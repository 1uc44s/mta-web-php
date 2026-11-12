<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Models\GameAccountLink;
use App\Models\MtaAccount;
use App\Models\MtaAccountDevice;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['roleplay.bridge.serial_pepper' => 'test-pepper-0123456789abcdef']);
    app(SettingsRepository::class)->set('features.devices', true);
});

function rawSerial(): string
{
    return 'A1B2C3D4E5F60718293A4B5C6D7E8F90';
}

/** @return array{user: User, account: MtaAccount, bridge: array<string, mixed>} */
function devicePlayer(): array
{
    $bridge = bridgeCredential();
    $user = User::factory()->create();
    $account = MtaAccount::query()->create(['mta_server_id' => $bridge['server']->id, 'mta_account_ref' => '1042', 'account_name' => 'kaan']);
    GameAccountLink::query()->create(['user_id' => $user->id, 'mta_account_id' => $account->id, 'status' => 'active', 'verification_method' => 'ingame_code', 'linked_at' => now()]);

    return ['user' => $user, 'account' => $account, 'bridge' => $bridge];
}

function deviceCode(User $user): string
{
    test()->actingAs($user)->post(route('panel.devices.link-code'))->assertRedirect();
    preg_match('#/cihazbagla ([0-9A-Z]{8})#', (string) test()->actingAs($user)->get(route('panel.devices.index'))->getContent(), $m);

    return $m[1] ?? '';
}

it('FR-ACC-010: cihaz kodu doğrulanır, onay bekler; ham serial hiçbir tabloda, logda veya yanıtta yoktur', function (): void {
    ['user' => $user, 'bridge' => $bridge] = devicePlayer();
    $code = deviceCode($user);

    $response = bridgeCall($bridge, 'POST', '/api/v1/game/device-link/verify', [
        'event_id' => (string) Str::uuid(), 'code' => $code, 'account' => ['ref' => '1042'], 'serial' => rawSerial(),
    ])->assertOk()->assertJsonPath('data.status', 'pending_approval');

    $device = MtaAccountDevice::query()->firstOrFail();
    expect($device->serial_hmac)->toBe(hash_hmac('sha256', strtolower(rawSerial()), 'test-pepper-0123456789abcdef'))
        ->and(stripos((string) $response->getContent(), rawSerial()))->toBeFalse();

    // Grep testi (ROADMAP Faz 8 çıkış kriteri): tüm tablolar ve log dosyaları.
    foreach (DB::select('SHOW TABLES') as $row) {
        $table = (string) array_values((array) $row)[0];
        $dump = (string) json_encode(DB::table($table)->get(), JSON_UNESCAPED_UNICODE);
        expect(stripos($dump, rawSerial()))->toBeFalse("{$table} ham serial içeriyor");
    }
    foreach (File::glob(storage_path('logs/*.log')) as $log) {
        expect(stripos((string) File::get($log), rawSerial()))->toBeFalse(basename($log).' ham serial içeriyor');
    }

    $this->actingAs($user)->get(route('panel.devices.index'))->assertOk()->assertSee($device->fingerprint())->assertDontSee(rawSerial());
});

it('FR-ACC-010: başka kullanıcının kodu ile cihaz eklenemez', function (): void {
    ['bridge' => $bridge] = devicePlayer();
    $stranger = User::factory()->create();
    $other = MtaAccount::query()->create(['mta_server_id' => $bridge['server']->id, 'mta_account_ref' => '2000', 'account_name' => 'baska']);
    GameAccountLink::query()->create(['user_id' => $stranger->id, 'mta_account_id' => $other->id, 'status' => 'active', 'verification_method' => 'ingame_code', 'linked_at' => now()]);

    bridgeCall($bridge, 'POST', '/api/v1/game/device-link/verify', [
        'event_id' => (string) Str::uuid(), 'code' => deviceCode($stranger), 'account' => ['ref' => '1042'], 'serial' => rawSerial(),
    ])->assertStatus(422)->assertJsonPath('error.code', 'LINK_CODE_INVALID');

    expect(MtaAccountDevice::query()->count())->toBe(0);
});

it('FR-ACC-010: bağlı hesabı olmayan oyuncu cihaz kodu üretemez', function (): void {
    $this->actingAs(User::factory()->create())->post(route('panel.devices.link-code'))->assertSessionHasErrors('code');
});

it('ADMIN §8: yetkili onayı PC + gerekçe ister, audit yazar; oyuncu cihazını kaldırabilir', function (): void {
    ['user' => $user, 'bridge' => $bridge] = devicePlayer();
    bridgeCall($bridge, 'POST', '/api/v1/game/device-link/verify', [
        'event_id' => (string) Str::uuid(), 'code' => deviceCode($user), 'account' => ['ref' => '1042'], 'serial' => rawSerial(),
    ])->assertOk();
    $device = MtaAccountDevice::query()->firstOrFail();
    $admin = staffUser('game_master');

    $this->actingAs($admin)->post(route('admin.devices.approve', $device->id), ['reason' => 'Oyuncu doğrulandı'])->assertRedirect(route('password.confirm'));
    $this->actingAs($admin)->withSession(confirmedPassword())->post(route('admin.devices.approve', $device->id), ['reason' => 'Oyuncu doğrulandı'])->assertRedirect();

    expect($device->fresh()->status)->toBe('active')
        ->and(DB::table('audit_logs')->where('action', AuditAction::DevicesApproved->value)->count())->toBe(1)
        ->and($user->notifications()->count())->toBe(1);

    $this->actingAs(User::factory()->create())->withSession(confirmedPassword())->post(route('panel.devices.revoke', $device->id))->assertNotFound();
    $this->actingAs($user)->withSession(confirmedPassword())->post(route('panel.devices.revoke', $device->id))->assertRedirect();
    expect($device->fresh()->status)->toBe('revoked');
});

it('FR-ACC-010: pepper yapılandırılmamışsa cihaz doğrulaması 503 döner', function (): void {
    ['user' => $user, 'bridge' => $bridge] = devicePlayer();
    $code = deviceCode($user);
    config(['roleplay.bridge.serial_pepper' => '']);

    bridgeCall($bridge, 'POST', '/api/v1/game/device-link/verify', [
        'event_id' => (string) Str::uuid(), 'code' => $code, 'account' => ['ref' => '1042'], 'serial' => rawSerial(),
    ])->assertStatus(503);
});
