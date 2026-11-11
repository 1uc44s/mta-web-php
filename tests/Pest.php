<?php

declare(strict_types=1);
use App\Models\IntegrationCredential;
use App\Models\MtaServer;
use App\Models\Role;
use App\Models\User;
use App\Services\Bridge\BridgeSigner;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
 * Pest yapılandırması. Test adları gereksinim kimliğiyle başlar: it('FR-SUP-006: …') (docs/RULES.md §11).
 */

pest()->extend(TestCase::class)->in('Feature', 'Unit');

/**
 * Faz 4 yardımcısı: rolleri seed eder, 2FA'sı onaylı ve e-postası doğrulanmış personel oluşturur.
 *
 * @param  string  ...$roles  Rol slug'ları (config/roles.php)
 */
function staffUser(string ...$roles): User
{
    if (Role::query()->doesntExist()) {
        (new AccessControlSeeder)->run();
    }

    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => encrypt(json_encode(['kod-1', 'kod-2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    foreach ($roles as $slug) {
        $role = Role::query()->where('slug', $slug)->firstOrFail();
        DB::table('user_roles')->insert(['user_id' => $user->id, 'role_id' => $role->id, 'created_at' => now()]);
    }

    $user->flushAuthz();

    return $user;
}

/** Parola onayı (password.confirm) yapılmış oturum verisi. */
function confirmedPassword(): array
{
    return ['auth.password_confirmed_at' => now()->getTimestamp()];
}

/**
 * Faz 7 yardımcısı: aktif bir MTA sunucusu + köprü kimlik bilgisi oluşturur.
 *
 * @param  list<string>  $scopes
 * @return array{server: MtaServer, credential: IntegrationCredential, secret: string}
 */
function bridgeCredential(array $scopes = IntegrationCredential::SCOPES): array
{
    $server = MtaServer::query()->firstOrCreate(['slug' => 'main'], [
        'name' => 'Test', 'connect_host' => '127.0.0.1', 'connect_port' => 22003, 'max_players' => 100, 'is_active' => true, 'is_primary' => true,
    ]);
    $secret = bin2hex(random_bytes(32));
    $credential = new IntegrationCredential([
        'mta_server_id' => $server->id, 'key_id' => 'mrp_test_'.bin2hex(random_bytes(6)), 'secret' => $secret, 'scopes' => $scopes,
    ]);
    $credential->setAttribute('created_at', now());
    $credential->save();

    return ['server' => $server, 'credential' => $credential, 'secret' => $secret];
}

/**
 * İmzalı köprü isteği (docs/ARCHITECTURE.md §7.2). `$override` ile başlıklar bozulabilir.
 *
 * @param  array{credential: IntegrationCredential, secret: string}  $bridge
 * @param  array<string, mixed>|null  $body
 * @param  array<string, string>  $override
 */
function bridgeCall(array $bridge, string $method, string $path, ?array $body = null, array $override = []): TestResponse
{
    $content = $body === null ? '' : json_encode($body, JSON_THROW_ON_ERROR);
    $timestamp = $override['timestamp'] ?? (string) time();
    $nonce = $override['nonce'] ?? bin2hex(random_bytes(32));
    $signature = $override['signature'] ?? BridgeSigner::sign($bridge['secret'], BridgeSigner::canonical($method, $path, $timestamp, $nonce, $content));

    return test()->call($method, $path, [], [], [], [
        'HTTP_X_BRIDGE_KEY' => $override['key'] ?? $bridge['credential']->key_id,
        'HTTP_X_BRIDGE_TIMESTAMP' => $timestamp,
        'HTTP_X_BRIDGE_NONCE' => $nonce,
        'HTTP_X_BRIDGE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], $content);
}
