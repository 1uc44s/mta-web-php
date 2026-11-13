<?php

declare(strict_types=1);

use App\Actions\Support\TicketWorkflow;
use App\Models\Application;
use App\Models\ApplicationType;
use App\Models\Branch;
use App\Models\Character;
use App\Models\GameAccountLink;
use App\Models\MtaAccount;
use App\Models\MtaAccountDevice;
use App\Models\PaymentOrder;
use App\Models\Sanction;
use App\Models\SupportAttachment;
use App\Models\SupportCategory;
use App\Models\User;
use App\Notifications\PanelNotice;
use App\Services\Settings\SettingsRepository;
use App\Services\Wallet\LedgerService;
use App\Services\Wallet\WalletService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
 * Faz 11 (R2) yayın kapısı — docs/ROADMAP.md 11.1–11.3, brief §16.4/§16.5; R2 kapsamı Faz 8–10.
 * R1 maddeleri ReleaseGateTest'te; burada R2 uçları: panel genelinde IDOR taraması, R2 ACP aramalarında SQL injection /
 * yansıyan XSS, R2 uçlarında X-Forwarded-For ile hız sınırı atlatma, R2 sayfalarında sorgu sayısı, sırların repo dışında
 * tutulması.
 */

uses(RefreshDatabase::class);

const R2_FEATURES = ['applications', 'sanctions', 'devices', 'wallet', 'payments', 'push', 'academy_quiz'];

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    foreach (R2_FEATURES as $feature) {
        app(SettingsRepository::class)->set('features.'.$feature, true);
    }
});

/**
 * Sahibi `$owner` olan her panel kaynağından bir örnek: rota parametresi → değer.
 *
 * @return array<string, string|int>
 */
function ownedPanelResources(User $owner): array
{
    $bridge = bridgeCredential();
    $account = MtaAccount::query()->create(['mta_server_id' => $bridge['server']->id, 'mta_account_ref' => '77', 'account_name' => 'sahip']);
    $link = GameAccountLink::query()->create(['user_id' => $owner->id, 'mta_account_id' => $account->id, 'status' => 'active', 'verification_method' => 'ingame_code', 'linked_at' => now()]);
    $character = Character::query()->create(['mta_server_id' => $bridge['server']->id, 'mta_account_id' => $account->id, 'mta_character_ref' => 'c-77', 'name' => 'Sahip Karakter', 'synced_at' => now()]);
    $device = MtaAccountDevice::query()->create(['mta_account_id' => $account->id, 'serial_hmac' => str_repeat('b', 64), 'status' => 'active']);

    $type = ApplicationType::query()->create(['slug' => 'r2-test', 'name' => 'R2', 'kind' => 'faction', 'form_key' => 'faction_default', 'branch_id' => Branch::query()->value('id'), 'is_open' => true]);
    $application = Application::query()->create(['user_id' => $owner->id, 'application_type_id' => $type->id, 'status' => 'draft']);
    $sanction = Sanction::query()->create(['user_id' => $owner->id, 'type' => 'warning', 'public_reason' => 'Sahibin cezası', 'starts_at' => now(), 'status' => 'active', 'appealable' => true, 'issued_by' => staffUser('moderator')->id]);

    $ticket = app(TicketWorkflow::class)->create($owner, SupportCategory::query()->firstOrFail(), 'Sahibin talebi', 'Sahibin talep açıklaması burada.', (string) Str::uuid());
    Storage::fake('local');
    Storage::disk('local')->put('support/r2.txt', 'x');
    $attachment = SupportAttachment::query()->create([
        'ulid' => strtolower((string) Str::ulid()), 'ticket_id' => $ticket->id, 'message_id' => $ticket->messages()->value('id'), 'uploaded_by' => $owner->id,
        'disk' => 'local', 'path' => 'support/r2.txt', 'original_name' => 'r2.txt', 'mime' => 'text/plain', 'size_bytes' => 1, 'sha256' => str_repeat('0', 64),
    ]);

    $wallet = app(WalletService::class)->for($owner);
    app(LedgerService::class)->post($wallet->id, 'credit', 50, 'promo', 'r2-gate', 'Test');
    $order = PaymentOrder::query()->create(['order_number' => 'MRP-R2-0001', 'user_id' => $owner->id, 'wallet_id' => $wallet->id, 'provider' => 'fake',
        'amount_minor' => 100, 'currency' => 'TRY', 'credit_amount' => 10, 'status' => 'pending', 'idempotency_key' => 'r2-gate']);

    $owner->notify(new PanelNotice('application_message', 'Sahibe bildirim', route('panel.dashboard')));
    $sessionId = Str::random(40);
    DB::table('sessions')->insert(['id' => $sessionId, 'user_id' => $owner->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => '', 'last_activity' => time()]);

    return [
        'application' => $application->ulid, 'character' => $character->ulid, 'device' => $device->id, 'link' => $link->id,
        'id' => (string) DB::table('notifications')->where('notifiable_id', $owner->id)->value('id'),
        'order' => $order->ulid, 'sanction' => $sanction->ulid, 'ticket' => $ticket->ulid, 'attachment' => $attachment->ulid, 'session' => $sessionId,
    ];
}

it('SEC/IDOR: başka oyuncuya ait kaynağı içeren her panel rotası erişimi reddeder (genel tarama)', function (): void {
    $owner = User::factory()->create();
    $params = ownedPanelResources($owner);
    $intruder = User::factory()->create();
    $checked = [];

    foreach (Route::getRoutes() as $route) {
        $name = (string) $route->getName();
        $names = $route->parameterNames();
        if (! str_starts_with($name, 'panel.') || array_intersect($names, array_keys($params)) === []) {
            continue;
        }
        $url = route($name, array_intersect_key($params, array_flip($names)));
        $method = in_array('POST', $route->methods(), true) ? 'post' : 'get';
        // Gövde: doğrulamadan geçebilecek makul değerler (sahiplik kontrolü doğrulamaya takılmadan sınansın).
        $body = ['body' => 'Yetkisiz deneme mesajı yeterince uzun.', 'reason' => 'Yetkisiz deneme gerekçesi yeterince uzun.', 'answers' => []];

        $response = $this->actingAs($intruder)->withSession(confirmedPassword())->{$method}($url, $method === 'post' ? $body : []);

        expect($response->status())->toBeIn([403, 404], "{$name} → {$response->status()}");
        $checked[] = $name;
    }

    // Tarama gerçekten R2 + R1 sahiplik rotalarını kapsıyor.
    expect($checked)->toContain('panel.applications.show', 'panel.sanctions.appeal', 'panel.payments.status', 'panel.devices.revoke',
        'panel.characters.show', 'panel.support.attachments.show', 'panel.notifications.read', 'panel.security.sessions.revoke')
        ->and(count($checked))->toBeGreaterThanOrEqual(18);

    // Sahibin verisi değişmedi.
    expect(DB::table('sessions')->where('id', $params['session'])->exists())->toBeTrue()
        ->and(DB::table('mta_account_devices')->where('id', $params['device'])->value('status'))->toBe('active')
        ->and(DB::table('applications')->where('ulid', $params['application'])->value('status'))->toBe('draft')
        ->and(DB::table('sanction_appeals')->count())->toBe(0)
        ->and(DB::table('notifications')->where('id', $params['id'])->value('read_at'))->toBeNull();
});

it('SEC: R2 ACP aramalarında SQL injection etkisiz, yansıyan XSS kaçışlanır', function (string $payload): void {
    $admin = staffUser('super_admin');

    foreach (['/admin/sanctions', '/admin/economy/transactions', '/admin/payments', '/admin/applications'] as $path) {
        $response = $this->actingAs($admin)->get($path.'?q='.urlencode($payload));
        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
    }
    $this->actingAs(User::factory()->create())->get('/panel/transactions?direction='.urlencode($payload))->assertOk();

    expect(DB::table('users')->count())->toBeGreaterThanOrEqual(2)
        ->and(DB::table('wallet_types')->count())->toBeGreaterThanOrEqual(1);
})->with([
    "' OR '1'='1' -- ",
    'x\'; DROP TABLE wallet_transactions; --',
    '1 UNION SELECT password FROM users',
    '<script>alert(1)</script>',
]);

it('SEC: KVKK dökümü hız sınırı X-Forwarded-For ile atlatılamaz (3/saat)', function (): void {
    $user = User::factory()->create();

    for ($i = 0; $i < 3; $i++) {
        $this->actingAs($user)->withSession(confirmedPassword())->withHeader('X-Forwarded-For', "203.0.113.{$i}")
            ->post(route('panel.account.export'))->assertOk();
    }

    $this->actingAs($user)->withSession(confirmedPassword())->withHeader('X-Forwarded-For', '198.51.100.9')
        ->post(route('panel.account.export'))->assertStatus(429);
});

it('SEC: ödeme webhook\'u sahte imzalı veya bilinmeyen sağlayıcılı olay için kayıt oluşturmaz', function (): void {
    $body = (string) json_encode(['id' => 'evt_r2', 'type' => 'payment.succeeded', 'order' => 'yok', 'amount_minor' => 100]);

    $this->call('POST', '/webhooks/payments/fake', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_FAKE_SIGNATURE' => str_repeat('0', 64)], $body)
        ->assertStatus(400);
    $this->call('POST', '/webhooks/payments/bilinmeyen', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)
        ->assertStatus(400); // bilinmeyen sağlayıcı: imza doğrulanamaz → 400, kayıt yok

    expect(DB::table('payment_events')->count())->toBe(0);
});

it('NFR-PERF: R2 sayfaları makul sorgu sayısıyla render edilir', function (string $url, int $max, string $who): void {
    $user = $who === 'admin' ? staffUser('super_admin') : User::factory()->create();
    $this->actingAs($user);

    $this->get($url);
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get($url)->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($count)->toBeLessThanOrEqual($max, "{$url}: {$count} sorgu");
})->with([
    'cüzdan' => ['/panel/wallet', 30, 'player'],
    'işlemler' => ['/panel/transactions', 30, 'player'],
    'başvurular' => ['/panel/applications', 30, 'player'],
    'cezalar' => ['/panel/sanctions', 30, 'player'],
    'akademi' => ['/panel/academy', 30, 'player'],
    'ayarlar' => ['/panel/settings', 30, 'player'],
    'ACP ekonomi' => ['/admin/economy', 45, 'admin'],
    'ACP ödemeler' => ['/admin/payments', 45, 'admin'],
    'ACP başvurular' => ['/admin/applications', 45, 'admin'],
    'ACP cezalar' => ['/admin/sanctions', 45, 'admin'],
]);

it('ROADMAP 11.6 (R2): takılı ödeme olayı kritik, başarısız oyun komutu uyarı üretir', function (): void {
    DB::table('mta_servers')->update(['last_heartbeat_at' => now(), 'is_maintenance' => false]);
    DB::table('payment_events')->insert(['provider' => 'fake', 'provider_event_id' => 'evt_stuck', 'event_type' => 'payment.succeeded', 'signature_valid' => true,
        'payload' => '{}', 'status' => 'received', 'received_at' => now()->subMinutes(20)]);

    $this->artisan('mrp:health-check', ['--json' => true])->expectsOutputToContain('"check":"payment_events_stuck"')->assertFailed();

    DB::table('payment_events')->update(['status' => 'processed']);
    $bridge = bridgeCredential();
    DB::table('game_commands')->insert(['ulid' => strtolower((string) Str::ulid()), 'mta_server_id' => $bridge['server']->id, 'type' => 'player.warn', 'payload' => '{}',
        'idempotency_key' => 'r2:failed', 'status' => 'failed', 'attempts' => 3, 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);

    $this->artisan('mrp:health-check', ['--json' => true])->expectsOutputToContain('"check":"game_commands_failed"')->assertFailed();
});

it('§16.5: sırlar repo dışında — .env izlenmez, .env.example gizli değer içermez', function (): void {
    $root = base_path();
    expect((string) file_get_contents($root.'/.gitignore'))->toContain('.env');

    $secretKeys = '/^(APP_KEY|DB_PASSWORD|MAIL_PASSWORD|REVERB_APP_SECRET|REVERB_APP_KEY|VAPID_PRIVATE_KEY|DISCORD_WEBHOOK_[A-Z_]+|BRIDGE_SERIAL_PEPPER|[A-Z_]*_SECRET|[A-Z_]*_TOKEN)=(.*)$/m';
    preg_match_all($secretKeys, (string) file_get_contents($root.'/.env.example'), $matches, PREG_SET_ORDER);

    expect($matches)->not->toBeEmpty();
    foreach ($matches as $match) {
        expect(in_array(trim($match[2], " \"'"), ['', 'null'], true))->toBeTrue("{$match[1]} .env.example içinde dolu");
    }
});
