<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Faz 11 (R1) yayın kapısı — brief §16.4 güvenlik testleri ve docs/ROADMAP.md 11.2–11.3. Diğer maddeler mevcut
 * testlerde: XSS (CmsTest), CSRF/Origin (Foundation), IDOR (SupportTest, AccountLinkTest), brute force (AuthFlowTest),
 * yetkisiz admin (AdminRouteProtectionTest), upload bypass (SupportTest), HMAC replay (BridgeSecurityTest),
 * hata sızıntısı (ErrorRenderingTest).
 */

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('SEC: SQL injection yükleri giriş, arama ve rota parametrelerinde etkisizdir', function (string $payload): void {
    $victim = User::factory()->create(['username' => 'kurban']);

    $this->post('/login', ['login' => $payload, 'password' => $payload])->assertSessionHasErrors();
    $this->assertGuest();

    $this->get('/news?q='.urlencode($payload))->assertSuccessful();
    $this->get('/news/'.rawurlencode($payload))->assertNotFound();
    $this->get('/factions/'.rawurlencode($payload))->assertNotFound();

    $admin = staffUser('super_admin');
    $this->actingAs($admin)->get('/admin/users?q='.urlencode($payload))->assertOk();
    $this->actingAs($admin)->get('/admin/game-accounts?q='.urlencode($payload))->assertOk();

    expect(User::query()->whereKey($victim->id)->exists())->toBeTrue()
        ->and(DB::table('users')->count())->toBeGreaterThanOrEqual(2);
})->with([
    "' OR '1'='1' -- ",
    'kurban\'; DROP TABLE users; --',
    '1 UNION SELECT password FROM users',
    '%\' AND SLEEP(2) AND \'%\'=\'',
]);

it('SEC: girişte oturum kimliği yenilenir (session fixation)', function (): void {
    User::factory()->create(['username' => 'oyuncu1', 'password' => 'Guclu-Parola-2026!']);

    $this->get('/login')->assertOk();
    $before = session()->getId();

    $this->post('/login', ['login' => 'oyuncu1', 'password' => 'Guclu-Parola-2026!'])->assertRedirect();

    $this->assertAuthenticated();
    expect(session()->getId())->not->toBe($before);
});

it('SEC: çıkışta oturum geçersizleşir ve eski kimlik tekrar kullanılamaz', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect();

    $this->assertGuest();
    $this->get('/panel')->assertRedirect(route('login'));
});

it('SEC: X-Forwarded-For sahteciliği giriş hız sınırını atlatamaz', function (): void {
    User::factory()->create(['username' => 'hedef']);

    for ($i = 0; $i < 5; $i++) {
        $this->withHeader('X-Forwarded-For', "203.0.113.{$i}")
            ->post('/login', ['login' => 'hedef', 'password' => 'yanlis-parola-'.$i]);
    }

    $this->withHeader('X-Forwarded-For', '198.51.100.77')
        ->post('/login', ['login' => 'hedef', 'password' => 'yanlis-parola-x'])
        ->assertStatus(429);
});

it('SEC: köprü hız sınırı farklı X-Forwarded-For ile atlatılamaz (heartbeat 6/dk)', function (): void {
    $bridge = bridgeCredential();

    for ($i = 0; $i < 6; $i++) {
        bridgeCall($bridge, 'POST', '/api/v1/game/heartbeat', ['players' => 1, 'max_players' => 10])->assertOk();
    }

    $this->withHeader('X-Forwarded-For', '198.51.100.1');
    bridgeCall($bridge, 'POST', '/api/v1/game/heartbeat', ['players' => 1, 'max_players' => 10])->assertStatus(429);
});

it('SEC: yansıyan XSS — sorgu parametreleri kaçışlanarak basılır', function (): void {
    $payload = '<script>alert(1)</script>';

    $this->get('/news?q='.urlencode($payload))->assertDontSee($payload, false);
    $this->get('/rules?q='.urlencode($payload))->assertDontSee($payload, false);

    $admin = staffUser('super_admin');
    $this->actingAs($admin)->get('/admin/users?q='.urlencode($payload))->assertDontSee($payload, false);
});

it('ROADMAP 11.6: mrp:health-check başarısız iş ve sessiz sunucu için alarm üretir', function (): void {
    DB::table('mta_servers')->update(['last_heartbeat_at' => now(), 'is_maintenance' => false]);
    DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x', 'failed_at' => now()]);

    $this->artisan('mrp:health-check', ['--json' => true])
        ->expectsOutputToContain('"check":"failed_jobs"')
        ->assertFailed();

    DB::table('failed_jobs')->delete();
    DB::table('mta_servers')->update(['last_heartbeat_at' => now()->subMinutes(10)]);

    $this->artisan('mrp:health-check', ['--json' => true])->expectsOutputToContain('"check":"heartbeat:')->assertFailed();
});

it('NFR-PERF: public ve panel sayfaları makul sorgu sayısıyla render edilir (N+1 yok)', function (string $url, int $max, bool $auth): void {
    if ($auth) {
        $this->actingAs(User::factory()->create());
    }

    $this->get($url); // cache ısıt
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get($url)->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($count)->toBeLessThanOrEqual($max, "{$url}: {$count} sorgu");
})->with([
    'ana sayfa' => ['/', 25, false],
    'haberler' => ['/news', 20, false],
    'kurallar' => ['/rules', 20, false],
    'fraksiyonlar' => ['/factions', 20, false],
    'panel' => ['/panel', 30, true],
    'oyun hesapları' => ['/panel/game-accounts', 30, true],
    'destek' => ['/panel/support', 30, true],
]);
