<?php

declare(strict_types=1);

use App\Actions\GameAccounts\LinkCodes;
use App\Enums\AuditAction;
use App\Models\Character;
use App\Models\GameAccountLink;
use App\Models\GameCommand;
use App\Models\GameLinkCode;
use App\Models\IntegrationCredential;
use App\Models\MtaAccount;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/** Panelden kod üretir ve düz metin kodu (yalnızca bir kez gösterilen) döndürür. */
function issueLinkCode(User $user): string
{
    test()->actingAs($user)->post(route('panel.game-accounts.link-code'))->assertRedirect(route('panel.game-accounts.index'));
    $html = (string) test()->actingAs($user)->get(route('panel.game-accounts.index'))->assertOk()->getContent();
    preg_match('#/webbagla ([0-9A-Z]{8})#', $html, $m);

    return $m[1] ?? '';
}

function verifyLink(array $bridge, string $code, string $ref = '1042', string $name = 'kaan'): TestResponse
{
    return bridgeCall($bridge, 'POST', '/api/v1/game/account-link/verify', [
        'event_id' => (string) Str::uuid(), 'code' => $code, 'account' => ['ref' => $ref, 'name' => $name],
    ]);
}

it('FR-ACC-001: kod 8 karakter Crockford Base32, DB\'de yalnızca HMAC saklanır, yenilemede tekrar gösterilmez', function (): void {
    $user = User::factory()->create();
    $code = issueLinkCode($user);

    expect($code)->toMatch('/^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{8}$/');

    $row = GameLinkCode::query()->firstOrFail();
    expect($row->code_hash)->toBe(LinkCodes::hash($code))->not->toContain($code)
        ->and($row->expires_at->diffInMinutes(now(), absolute: true))->toBeGreaterThan(9.0);

    $this->actingAs($user)->get(route('panel.game-accounts.index'))->assertDontSee('/webbagla '.$code);
});

it('FR-ACC-002: yeni kod öncekini iptal eder (tek aktif kod)', function (): void {
    $user = User::factory()->create();
    issueLinkCode($user);
    issueLinkCode($user);

    expect(GameLinkCode::query()->whereNull('cancelled_at')->count())->toBe(1)
        ->and(GameLinkCode::query()->whereNotNull('cancelled_at')->count())->toBe(1);
});

it('FR-ACC-002: kod üretimi saatte 5 ile sınırlıdır', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($user)->post(route('panel.game-accounts.link-code'))->assertRedirect();
    }

    $this->actingAs($user)->post(route('panel.game-accounts.link-code'))->assertStatus(429);
});

it('FR-ACC-004: geçerli kod hesabı bağlar, kod tek kullanımlıktır ve audit yazılır', function (): void {
    $bridge = bridgeCredential();
    $user = User::factory()->create();
    $code = issueLinkCode($user);

    verifyLink($bridge, LinkCodes::display($code))
        ->assertOk()->assertJsonPath('data.linked', true)->assertJsonPath('data.web_username', $user->username);

    $link = GameAccountLink::query()->firstOrFail();
    expect($link->user_id)->toBe($user->id)->and($link->status)->toBe('active')->and($link->verification_method)->toBe('ingame_code')
        ->and(GameLinkCode::query()->first()->used_at)->not->toBeNull()
        ->and(DB::table('audit_logs')->where('action', AuditAction::AccountsLinked->value)->count())->toBe(1);

    verifyLink($bridge, $code, '2000', 'baska')->assertStatus(422)->assertJsonPath('error.code', 'LINK_CODE_INVALID');

    $this->actingAs($user)->getJson(route('panel.game-accounts.link-status'))
        ->assertOk()->assertJsonPath('data.state', 'linked')->assertJsonPath('data.account_name', 'kaan')
        ->assertJsonMissingPath('data.code');
});

it('FR-ACC-003: süresi dolmuş kod reddedilir', function (): void {
    $bridge = bridgeCredential();
    $user = User::factory()->create();
    $code = issueLinkCode($user);

    $this->travel(11)->minutes();

    verifyLink($bridge, $code)->assertStatus(422)->assertJsonPath('error.code', 'LINK_CODE_INVALID');
    expect(GameAccountLink::query()->count())->toBe(0);
    $this->actingAs($user)->getJson(route('panel.game-accounts.link-status'))->assertJsonPath('data.state', 'expired');
});

it('FR-ACC-003: hesap başına 5 yanlış denemeden sonra 429 döner', function (): void {
    $bridge = bridgeCredential();

    for ($i = 0; $i < 5; $i++) {
        verifyLink($bridge, 'AAAAAAAA')->assertStatus(422);
    }

    verifyLink($bridge, 'AAAAAAAA')->assertStatus(429)->assertJsonPath('error.code', 'RATE_LIMITED');
});

it('FR-ACC-005: başka web hesabına bağlı oyun hesabı 409 ACCOUNT_ALREADY_LINKED', function (): void {
    $bridge = bridgeCredential();
    $first = User::factory()->create();
    $second = User::factory()->create();

    verifyLink($bridge, issueLinkCode($first))->assertOk();
    verifyLink($bridge, issueLinkCode($second))->assertStatus(409)->assertJsonPath('error.code', 'ACCOUNT_ALREADY_LINKED');

    expect(GameAccountLink::query()->count())->toBe(1);
});

it('FR-ACC-005: DB seviyesinde bir oyun hesabının tek aktif bağlantısı olur', function (): void {
    $server = bridgeCredential()['server'];
    $account = MtaAccount::query()->create(['mta_server_id' => $server->id, 'mta_account_ref' => '7', 'account_name' => 'x']);
    $make = fn (): GameAccountLink => GameAccountLink::query()->create(['user_id' => User::factory()->create()->id, 'mta_account_id' => $account->id, 'status' => 'active', 'verification_method' => 'ingame_code', 'linked_at' => now()]);

    $make();
    expect($make)->toThrow(UniqueConstraintViolationException::class);
});

it('FR-ACC-005: kullanıcı başına bağlantı sınırı uygulanır', function (): void {
    $bridge = bridgeCredential();
    $user = User::factory()->create();

    foreach (['1', '2', '3'] as $ref) {
        verifyLink($bridge, issueLinkCode($user), $ref, 'h'.$ref)->assertOk();
    }

    $this->actingAs($user)->post(route('panel.game-accounts.link-code'))->assertSessionHasErrors('code');
});

it('FR-BRG-007: aynı event_id ile tekrar gelen doğrulama yan etkisiz duplicate döner', function (): void {
    $bridge = bridgeCredential();
    $user = User::factory()->create();
    $body = ['event_id' => (string) Str::uuid(), 'code' => issueLinkCode($user), 'account' => ['ref' => '1042', 'name' => 'kaan']];

    bridgeCall($bridge, 'POST', '/api/v1/game/account-link/verify', $body)->assertOk();
    bridgeCall($bridge, 'POST', '/api/v1/game/account-link/verify', $body)->assertOk()->assertJsonPath('data.duplicate', true);

    expect(GameAccountLink::query()->count())->toBe(1);
});

it('FR-ACC-006: kaldırma talebi → onay; köprüye link_revoked komutu kuyruğa girer', function (): void {
    $bridge = bridgeCredential();
    $user = User::factory()->create();
    verifyLink($bridge, issueLinkCode($user))->assertOk();
    $link = GameAccountLink::query()->firstOrFail();

    $this->actingAs($user)->withSession(confirmedPassword())
        ->post(route('panel.game-accounts.unlink-request', $link->id), ['reason' => 'Hesabı artık kullanmıyorum.'])->assertRedirect();
    expect($link->fresh()->status)->toBe('unlink_requested');

    $admin = staffUser('super_admin');
    $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.game-accounts.approve-unlink', $link->id), ['reason' => 'Oyuncu talebi onaylandı.'])->assertRedirect();

    expect($link->fresh()->status)->toBe('unlinked')
        ->and(GameCommand::query()->where('type', 'account.link_revoked')->count())->toBe(1)
        ->and(GameCommand::query()->first()->payload)->toBe(['account_ref' => '1042'])
        ->and(DB::table('audit_logs')->where('action', AuditAction::AccountsUnlinked->value)->count())->toBe(1);
});

it('FR-ACC-006: başkasının bağlantısı için kaldırma talebi 404', function (): void {
    $bridge = bridgeCredential();
    $owner = User::factory()->create();
    verifyLink($bridge, issueLinkCode($owner))->assertOk();
    $link = GameAccountLink::query()->firstOrFail();

    $this->actingAs(User::factory()->create())->withSession(confirmedPassword())
        ->post(route('panel.game-accounts.unlink-request', $link->id), ['reason' => 'Bu benim değil ama deniyorum.'])->assertNotFound();
});

it('FR-ACC-008: karakter listesi yalnızca aktif bağlantılı hesapları içerir; başkasının karakteri 404', function (): void {
    $bridge = bridgeCredential();
    $owner = User::factory()->create();
    verifyLink($bridge, issueLinkCode($owner))->assertOk();
    bridgeCall($bridge, 'POST', '/api/v1/game/characters/sync', ['event_id' => (string) Str::uuid(), 'account_ref' => '1042', 'characters' => [
        ['ref' => 'c-1', 'name' => 'Kaan Demir', 'public_profile' => ['bio' => 'Merhaba dünya']],
    ]])->assertOk();
    $character = Character::query()->firstOrFail();

    $this->actingAs($owner)->get(route('panel.characters.index'))->assertOk()->assertSee('Kaan Demir');
    $this->actingAs($owner)->get(route('panel.characters.show', $character->ulid))->assertOk()->assertSee('Merhaba dünya');
    $this->actingAs(User::factory()->create())->get(route('panel.characters.show', $character->ulid))->assertNotFound();
});

it('ROUTES §6.6: karakter profilini gizleme gerekçe ister ve audit yazar', function (): void {
    $bridge = bridgeCredential();
    $owner = User::factory()->create();
    verifyLink($bridge, issueLinkCode($owner))->assertOk();
    bridgeCall($bridge, 'POST', '/api/v1/game/characters/sync', ['event_id' => (string) Str::uuid(), 'account_ref' => '1042', 'characters' => [
        ['ref' => 'c-1', 'name' => 'Kaan Demir', 'public_profile' => ['bio' => 'Gizlenecek metin']],
    ]])->assertOk();
    $character = Character::query()->firstOrFail();
    $admin = staffUser('super_admin');

    $this->actingAs($admin)->post(route('admin.characters.visibility', $character->ulid), ['hidden' => 1])->assertSessionHasErrors('reason');
    $this->actingAs($admin)->post(route('admin.characters.visibility', $character->ulid), ['hidden' => 1, 'reason' => 'Uygunsuz içerik'])->assertRedirect();

    expect($character->fresh()->is_profile_hidden)->toBeTrue()
        ->and(DB::table('audit_logs')->where('action', AuditAction::CharactersVisibilityChanged->value)->count())->toBe(1);
    $this->actingAs($owner)->get(route('panel.characters.show', $character->ulid))->assertDontSee('Gizlenecek metin');
});

it('FR-BRG-004: ACP anahtar üretir, secret yalnızca bir kez görünür ve iptal edilebilir', function (): void {
    $server = bridgeCredential()['server'];
    $admin = staffUser('super_admin');

    $html = (string) $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.integrations.mta.credentials.store', $server->id), ['scopes' => ['heartbeat'], 'reason' => 'Yeni sunucu kurulumu'])
        ->assertOk()->getContent();

    $credential = IntegrationCredential::query()->latest('id')->firstOrFail();
    expect($html)->toContain($credential->key_id)->toContain($credential->secret);

    $this->actingAs($admin)->get(route('admin.integrations.mta'))->assertOk()->assertSee($credential->key_id)->assertDontSee($credential->secret);

    $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.integrations.mta.credentials.revoke', $credential->id), ['reason' => 'Rotasyon'])->assertRedirect();
    expect($credential->fresh()->revoked_at)->not->toBeNull();
});

it('FR-BRG-004: sunucu başına en fazla 2 aktif anahtar', function (): void {
    $bridge = bridgeCredential();
    bridgeCredential();
    $admin = staffUser('super_admin');

    $this->actingAs($admin)->withSession(confirmedPassword())
        ->post(route('admin.integrations.mta.credentials.store', $bridge['server']->id), ['scopes' => ['heartbeat'], 'reason' => 'Üçüncü anahtar'])
        ->assertSessionHasErrors('scopes');
});

it('SEC-010: oyun hesabı ve entegrasyon ekranları izinsiz personele 403', function (): void {
    $staff = staffUser('support_agent');

    $this->actingAs($staff)->get(route('admin.integrations.mta'))->assertForbidden();
});
