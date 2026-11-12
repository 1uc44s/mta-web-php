<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\UserStatus;
use App\Models\GameAccountLink;
use App\Models\GameCommand;
use App\Models\IntegrationCredential;
use App\Models\MtaAccount;
use App\Models\MtaServer;
use App\Models\Sanction;
use App\Models\SanctionAppeal;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(SettingsRepository::class)->set('features.sanctions', true);
});

/** @return array{user: User, account: MtaAccount, bridge: array<string, mixed>} */
function sanctionTarget(): array
{
    $bridge = bridgeCredential();
    $user = User::factory()->create();
    $account = MtaAccount::query()->create(['mta_server_id' => $bridge['server']->id, 'mta_account_ref' => '1042', 'account_name' => 'kaan']);
    GameAccountLink::query()->create(['user_id' => $user->id, 'mta_account_id' => $account->id, 'status' => 'active', 'verification_method' => 'ingame_code', 'linked_at' => now()]);

    return ['user' => $user, 'account' => $account, 'bridge' => $bridge];
}

/** @param  array<string, mixed>  $data */
function issueSanction(User $actor, array $data): TestResponse
{
    return test()->actingAs($actor)->withSession(confirmedPassword())->post(route('admin.sanctions.store'), $data);
}

it('FR-MOD-003: süreli yasak cezası idempotent oyun komutu üretir ve audit yazılır', function (): void {
    ['user' => $user, 'account' => $account] = sanctionTarget();
    $mod = staffUser('moderator');

    issueSanction($mod, ['type' => 'temp_ban', 'user' => $user->username, 'duration_minutes' => 60, 'public_reason' => 'RDM — RP-4.2 ihlali', 'internal_note' => 'Gizli not: video 03:12'])
        ->assertRedirect();

    $sanction = Sanction::query()->firstOrFail();
    expect($sanction->mta_account_id)->toBe($account->id)->and($sanction->ends_at)->not->toBeNull();

    $command = GameCommand::query()->firstOrFail();
    expect($command->type)->toBe('player.ban')
        ->and($command->payload)->toBe(['account_ref' => '1042', 'minutes' => 60, 'reason' => 'RDM — RP-4.2 ihlali'])
        ->and($command->getAttribute('idempotency_key'))->toBe('sanction:'.$sanction->ulid.':apply')
        ->and(DB::table('audit_logs')->where('action', AuditAction::SanctionsCreated->value)->count())->toBe(1);
});

it('FR-MOD-004: oyuncu gerekçeyi görür; iç not, kanıt ve veren yetkili görünmez', function (): void {
    ['user' => $user] = sanctionTarget();
    $mod = staffUser('moderator');
    issueSanction($mod, ['type' => 'warning', 'user' => $user->username, 'public_reason' => 'Sohbet kurallarına uy', 'internal_note' => 'GIZLI-NOT-123', 'evidence_url' => 'https://example.com/kanit-xyz']);
    $sanction = Sanction::query()->firstOrFail();

    $this->actingAs($user)->get(route('panel.sanctions.index'))->assertOk()->assertSee('Sohbet kurallarına uy');
    $this->actingAs($user)->get(route('panel.sanctions.show', $sanction))->assertOk()
        ->assertSee('Yönetim Ekibi')->assertDontSee('GIZLI-NOT-123')->assertDontSee('kanit-xyz')->assertDontSee($mod->username);

    $this->actingAs(User::factory()->create())->get(route('panel.sanctions.show', $sanction))->assertNotFound();
});

it('FR-MOD-002: kalıcı yasak sanctions.ban_permanent ve kanıt bağlantısı ister', function (): void {
    ['user' => $user] = sanctionTarget();

    issueSanction(staffUser('moderator'), ['type' => 'perm_ban', 'user' => $user->username, 'public_reason' => 'Hile kullanımı', 'evidence_url' => 'https://example.com/a'])->assertForbidden();
    issueSanction(staffUser('game_master'), ['type' => 'perm_ban', 'user' => $user->username, 'public_reason' => 'Hile kullanımı'])->assertSessionHasErrors('evidence_url');
    issueSanction(staffUser('game_master'), ['type' => 'perm_ban', 'user' => $user->username, 'public_reason' => 'Hile kullanımı', 'evidence_url' => 'https://example.com/a'])->assertRedirect();

    expect(GameCommand::query()->firstOrFail()->payload['minutes'])->toBeNull();
});

it('FR-MOD-006: web askısı kullanıcıyı askıya alır, süresi dolunca zamanlayıcı geri alır', function (): void {
    ['user' => $user] = sanctionTarget();
    issueSanction(staffUser('game_master'), ['type' => 'web_suspension', 'user' => $user->username, 'duration_minutes' => 30, 'public_reason' => 'Forum taciz'])->assertRedirect();

    expect($user->fresh()->status)->toBe(UserStatus::Suspended)->and(GameCommand::query()->count())->toBe(0);

    $this->travel(31)->minutes();
    $this->artisan('mrp:moderation-tick')->assertSuccessful();

    expect($user->fresh()->status)->toBe(UserStatus::Active)->and(Sanction::query()->firstOrFail()->status)->toBe('expired');
});

it('FR-MOD-001: kendine ceza ve üst rütbeye ceza engellenir', function (): void {
    $mod = staffUser('moderator');

    issueSanction($mod, ['type' => 'web_suspension', 'user' => $mod->username, 'duration_minutes' => 10, 'public_reason' => 'Deneme cezası'])->assertSessionHasErrors('user');
    issueSanction($mod, ['type' => 'web_suspension', 'user' => staffUser('admin')->username, 'duration_minutes' => 10, 'public_reason' => 'Deneme cezası'])->assertSessionHasErrors('user');
});

it('FR-MOD-005: itiraz tek seferlik; cezayı veren karar veremez; kabul cezayı kaldırır ve unban komutu kuyruğa girer', function (): void {
    ['user' => $user] = sanctionTarget();
    $issuer = staffUser('game_master');
    issueSanction($issuer, ['type' => 'temp_ban', 'user' => $user->username, 'duration_minutes' => 1440, 'public_reason' => 'Combat log yapıldı', 'appealable' => 1]);
    $sanction = Sanction::query()->firstOrFail();

    $body = 'Bağlantım koptu, kasıtlı değildi. Log kayıtlarını inceleyebilirsiniz lütfen.';
    $this->actingAs($user)->post(route('panel.sanctions.appeal', $sanction), ['body' => $body])->assertRedirect();
    $this->actingAs($user)->post(route('panel.sanctions.appeal', $sanction), ['body' => $body])->assertSessionHasErrors('body');
    $appeal = SanctionAppeal::query()->firstOrFail();

    $this->actingAs($issuer)->post(route('admin.appeals.decide', $appeal), ['decision' => 'accepted', 'note_public' => 'Kabul edildi.'])->assertSessionHasErrors('decision');
    $this->actingAs(staffUser('admin'))->post(route('admin.appeals.decide', $appeal), ['decision' => 'accepted', 'note_public' => 'Log incelendi, kabul.'])->assertRedirect();

    expect($sanction->fresh()->status)->toBe('revoked')
        ->and($appeal->fresh()->status)->toBe('accepted')
        ->and(GameCommand::query()->where('type', 'player.unban')->count())->toBe(1)
        ->and(DB::table('audit_logs')->where('action', AuditAction::SanctionsAppealDecided->value)->count())->toBe(1);
});

it('FR-BRG-011: ceza → komut → pending → result döngüsü cezaya bağlanır', function (): void {
    ['user' => $user, 'bridge' => $bridge] = sanctionTarget();
    issueSanction(staffUser('moderator'), ['type' => 'kick', 'user' => $user->username, 'public_reason' => 'AFK sınırı aşıldı']);
    $sanction = Sanction::query()->firstOrFail();

    $pending = bridgeCall($bridge, 'GET', '/api/v1/game/commands/pending')->assertOk()->json('data');
    expect($pending)->toHaveCount(1)->and($pending[0]['type'])->toBe('player.kick');

    bridgeCall($bridge, 'GET', '/api/v1/game/commands/pending')->assertOk()->assertJsonCount(0, 'data');
    bridgeCall($bridge, 'POST', '/api/v1/game/commands/result', ['id' => $pending[0]['id'], 'status' => 'succeeded', 'detail' => ['applied_at' => now()->toIso8601String()]])
        ->assertOk()->assertJsonPath('data.status', 'succeeded');
    bridgeCall($bridge, 'POST', '/api/v1/game/commands/result', ['id' => $pending[0]['id'], 'status' => 'failed', 'detail' => ['error' => 'player_offline']])
        ->assertOk()->assertJsonPath('data.duplicate', true);

    $this->actingAs(staffUser('moderator'))->get(route('admin.sanctions.show', $sanction))->assertOk()->assertSee('player.kick')->assertSee('Uygulandı');
});

it('FR-BRG-011: sonuçsuz komut 2 dk sonra yeniden kuyruğa girer, 3 denemeden sonra failed olur', function (): void {
    ['user' => $user, 'bridge' => $bridge] = sanctionTarget();
    issueSanction(staffUser('moderator'), ['type' => 'kick', 'user' => $user->username, 'public_reason' => 'AFK sınırı aşıldı']);

    for ($i = 1; $i <= 3; $i++) {
        bridgeCall($bridge, 'GET', '/api/v1/game/commands/pending')->assertJsonCount(1, 'data');
        $this->travel(3)->minutes();
        $this->artisan('mrp:moderation-tick');
    }

    $command = GameCommand::query()->firstOrFail();
    expect($command->status)->toBe('failed')->and($command->attempts)->toBe(3)
        ->and(DB::table('audit_logs')->where('action', AuditAction::GameCommandsFailed->value)->count())->toBe(1);
});

it('FR-BRG-011: komut uçları commands kapsamı ister ve başka sunucunun komutunu göremez', function (): void {
    ['user' => $user] = sanctionTarget();
    issueSanction(staffUser('moderator'), ['type' => 'kick', 'user' => $user->username, 'public_reason' => 'AFK sınırı aşıldı']);

    bridgeCall(bridgeCredential(['heartbeat']), 'GET', '/api/v1/game/commands/pending')->assertForbidden();

    $other = MtaServer::query()->create(['slug' => 'test2', 'name' => 'Test 2', 'connect_host' => '127.0.0.2', 'connect_port' => 22004, 'is_active' => true]);
    $credential = new IntegrationCredential(['mta_server_id' => $other->id, 'key_id' => 'mrp_test_other01', 'secret' => str_repeat('cd', 32), 'scopes' => ['commands']]);
    $credential->setAttribute('created_at', now());
    $credential->save();

    bridgeCall(['credential' => $credential, 'secret' => str_repeat('cd', 32)], 'GET', '/api/v1/game/commands/pending')->assertOk()->assertJsonCount(0, 'data');
});

it('ARCH §7.5: elle komutta tür bazlı izin uygulanır', function (): void {
    ['account' => $account] = sanctionTarget();
    $mod = staffUser('moderator');
    $serverId = $account->mta_server_id;

    $this->actingAs($mod)->withSession(confirmedPassword())->post(route('admin.game-commands.store'), ['type' => 'server.announce', 'mta_server_id' => $serverId, 'message' => 'Restart 5 dk'])->assertForbidden();
    $this->actingAs($mod)->withSession(confirmedPassword())->post(route('admin.game-commands.store'), ['type' => 'player.ban', 'mta_server_id' => $serverId, 'account' => $account->ulid, 'message' => 'x'])->assertForbidden();
    $this->actingAs($mod)->withSession(confirmedPassword())->post(route('admin.game-commands.store'), ['type' => 'player.kick', 'mta_server_id' => $serverId, 'account' => $account->ulid, 'message' => 'Kural'])->assertRedirect();

    expect(GameCommand::query()->where('type', 'player.kick')->count())->toBe(1)
        ->and(DB::table('audit_logs')->where('action', AuditAction::GameCommandsDispatched->value)->count())->toBe(1);
});

it('ROUTES §5.6: özellik kapalıyken panel ceza sayfaları 404', function (): void {
    app(SettingsRepository::class)->set('features.sanctions', false);

    $this->actingAs(User::factory()->create())->get('/panel/sanctions')->assertNotFound();
});
