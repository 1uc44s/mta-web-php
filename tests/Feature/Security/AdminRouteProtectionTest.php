<?php

declare(strict_types=1);

use App\Actions\Support\TicketWorkflow;
use App\Enums\AuditAction;
use App\Models\Application;
use App\Models\ApplicationType;
use App\Models\Branch;
use App\Models\Character;
use App\Models\ContactMessage;
use App\Models\GameAccountLink;
use App\Models\GameCommand;
use App\Models\Media;
use App\Models\MessageTemplate;
use App\Models\MtaAccount;
use App\Models\MtaAccountDevice;
use App\Models\Page;
use App\Models\PaymentOrder;
use App\Models\Refund;
use App\Models\Role;
use App\Models\Rule;
use App\Models\Sanction;
use App\Models\SanctionAppeal;
use App\Models\SupportAttachment;
use App\Models\SupportCategory;
use App\Models\User;
use App\Models\WalletAdjustmentRequest;
use App\Services\Audit\AuditLogger;
use App\Services\Content\ContentResourceRegistry;
use App\Services\Wallet\LedgerService;
use App\Services\Wallet\WalletService;
use Database\Seeders\ContentSeeder;
use Database\Seeders\EconomySeeder;
use Database\Seeders\MessageTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/*
 * docs/SRS.md SEC-010, FR-ADM-001/002: her admin.* rotası × (misafir, oyuncu, 2FA'sız personel, izinsiz personel).
 * Yeni bir admin rotası eklendiğinde bu test otomatik olarak onu da kapsar.
 */

uses(RefreshDatabase::class);

/** @return list<array{route: RouteObject, method: string, url: string, can: string|null}> */
function adminRouteCases(): array
{
    $target = User::factory()->create();
    $role = Role::query()->where('slug', 'moderator')->firstOrFail();
    $branch = Branch::query()->firstOrFail();
    $log = app(AuditLogger::class)->record(AuditAction::BranchesUpdated, $branch, [], null, null, 'system');
    $page = Page::query()->where('slug', 'home')->firstOrFail();
    $media = Media::query()->create(['disk' => 'public', 'path' => 'media/yok.webp', 'variants' => [], 'original_name' => 'yok.png', 'mime' => 'image/webp', 'width' => 10, 'height' => 10, 'size_bytes' => 1, 'alt_text' => 'Deneme']);
    $message = ContactMessage::query()->create(['name' => 'Ad', 'email' => 'a@example.com', 'subject' => 'Konu', 'body' => 'Metin', 'status' => 'new']);
    $ticket = app(TicketWorkflow::class)->create($target, SupportCategory::query()->firstOrFail(), 'Deneme talebi', 'Deneme talebinin açıklaması burada.', (string) Str::uuid());
    Storage::fake('local');
    Storage::disk('local')->put('support/yok.txt', 'x');
    $attachment = SupportAttachment::query()->create([
        'ulid' => strtolower((string) Str::ulid()), 'ticket_id' => $ticket->id, 'message_id' => $ticket->messages()->value('id'), 'uploaded_by' => $target->id,
        'disk' => 'local', 'path' => 'support/yok.txt', 'original_name' => 'yok.txt', 'mime' => 'text/plain', 'size_bytes' => 1, 'sha256' => str_repeat('0', 64),
    ]);

    // Faz 7: oyun hesabı, bağlantı, karakter ve köprü anahtarı
    $bridge = bridgeCredential();
    $gameAccount = MtaAccount::query()->create(['mta_server_id' => $bridge['server']->id, 'mta_account_ref' => '1', 'account_name' => 'oyuncu']);
    $gameLink = GameAccountLink::query()->create(['user_id' => $target->id, 'mta_account_id' => $gameAccount->id, 'status' => 'active', 'verification_method' => 'ingame_code', 'linked_at' => now()]);
    $character = Character::query()->create(['mta_server_id' => $bridge['server']->id, 'mta_account_id' => $gameAccount->id, 'mta_character_ref' => 'c-1', 'name' => 'Karakter', 'synced_at' => now()]);

    // Faz 8: başvuru, ceza, itiraz, komut, cihaz
    $appType = ApplicationType::query()->create(['slug' => 'test', 'name' => 'Test', 'kind' => 'faction', 'form_key' => 'faction_default', 'branch_id' => $branch->id, 'is_open' => true]);
    $application = Application::query()->create(['user_id' => $target->id, 'application_type_id' => $appType->id, 'status' => 'submitted', 'submitted_at' => now()]);
    $sanction = Sanction::query()->create(['user_id' => $target->id, 'mta_account_id' => $gameAccount->id, 'type' => 'warning', 'public_reason' => 'Deneme', 'starts_at' => now(), 'status' => 'active', 'issued_by' => $target->id]);
    $appeal = SanctionAppeal::query()->create(['sanction_id' => $sanction->id, 'user_id' => $target->id, 'body' => 'İtiraz metni', 'status' => 'submitted']);
    $command = GameCommand::query()->create(['mta_server_id' => $bridge['server']->id, 'type' => 'player.warn', 'payload' => ['account_ref' => '1'], 'idempotency_key' => 'test:1', 'expires_at' => now()->addDay()]);
    $device = MtaAccountDevice::query()->create(['mta_account_id' => $gameAccount->id, 'serial_hmac' => str_repeat('a', 64), 'status' => 'pending_approval']);

    // Faz 9: cüzdan, ledger kaydı, düzeltme, sipariş, iade
    (new EconomySeeder)->run();
    $wallet = app(WalletService::class)->for($target);
    $transaction = app(LedgerService::class)->post($wallet->id, 'credit', 100, 'promo', 'route-test', 'Test');
    $order = PaymentOrder::query()->create(['order_number' => 'MRP-TEST-0001', 'user_id' => $target->id, 'wallet_id' => $wallet->id, 'provider' => 'fake',
        'amount_minor' => 100, 'currency' => 'TRY', 'credit_amount' => 10, 'status' => 'succeeded', 'idempotency_key' => 'route-test']);
    $economy = [
        'wallet' => $wallet, 'transaction' => $transaction, 'order' => $order,
        'adjustment' => WalletAdjustmentRequest::query()->create(['wallet_id' => $wallet->id, 'direction' => 'credit', 'amount' => 1, 'reason' => str_repeat('a', 20), 'requires_second_approval' => false, 'requested_by' => $target->id]),
        'refund' => Refund::query()->create(['payment_order_id' => $order->id, 'amount_minor' => 50, 'reason' => 'Test iadesi', 'requested_by' => $target->id]),
    ];

    $base = [
        'application' => $application->ulid, 'sanction' => $sanction->ulid, 'appeal' => $appeal->ulid, 'command' => $command->ulid, 'device' => $device->id,
        'wallet' => $economy['wallet']->id, 'transaction' => $economy['transaction']->ulid, 'adjustment' => $economy['adjustment']->ulid,
        'order' => $economy['order']->ulid, 'refund' => $economy['refund']->ulid,
        'account' => $gameAccount->ulid, 'link' => $gameLink->id, 'character' => $character->ulid,
        'server' => $bridge['server']->id, 'credential' => $bridge['credential']->id,
        'user' => $target->ulid, 'role' => $role->id, 'branch' => $branch->id, 'log' => $log->ulid, 'group' => 'site', 'uuid' => 'yok',
        'page' => $page->id, 'section' => $page->sections()->value('id'), 'revision' => 999999, 'rule' => Rule::query()->value('id'),
        'template' => MessageTemplate::query()->value('id'), 'media' => $media->ulid, 'message' => $message->ulid,
        'ticket' => $ticket->ulid, 'attachment' => $attachment->ulid,
    ];

    $cases = [];
    foreach (Route::getRoutes() as $route) {
        $name = (string) $route->getName();
        if (! str_starts_with($name, 'admin.')) {
            continue;
        }
        $params = $base;
        $resource = $route->defaults['resource'] ?? null;
        if (is_string($resource)) {
            $params['id'] = ContentResourceRegistry::get($resource)['model']::query()->value('id') ?? 999999;
        }
        $params = array_intersect_key($params, array_flip($route->parameterNames()));
        $url = str_ends_with($name, '.preview') ? URL::temporarySignedRoute($name, now()->addMinutes(5), $params) : route($name, $params);
        $can = collect($route->gatherMiddleware())->first(fn ($m) => is_string($m) && str_starts_with($m, 'can:'));
        $cases[] = [
            'route' => $route, 'method' => in_array('POST', $route->methods(), true) ? 'POST' : 'GET', 'url' => $url,
            'can' => $can ? substr($can, 4) : null, 'missing' => ($params['id'] ?? 0) === 999999,
        ];
    }

    return $cases;
}

beforeEach(function (): void {
    staffUser(); // rolleri seed et
    (new ContentSeeder)->run();
    (new MessageTemplateSeeder)->run();
});

it('SEC-010: admin rotası kataloğu boş değildir ve dashboard dışındaki her rota can: taşır', function (): void {
    $cases = adminRouteCases();
    expect(count($cases))->toBeGreaterThan(25);

    foreach ($cases as $case) {
        if ($case['route']->getName() !== 'admin.dashboard') {
            expect($case['can'])->not->toBeNull($case['route']->getName().' can: middleware taşımıyor');
        }
    }
});

it('FR-ADM-001: misafir her admin rotasında girişe yönlendirilir', function (): void {
    foreach (adminRouteCases() as $case) {
        $this->call($case['method'], $case['url'])->assertRedirect(route('login'));
    }
});

it('FR-ADM-001: personel olmayan doğrulanmış oyuncu her admin rotasında 404 alır', function (): void {
    $player = User::factory()->create();
    foreach (adminRouteCases() as $case) {
        $this->actingAs($player)->call($case['method'], $case['url'])->assertNotFound();
    }
});

it('FR-AUTH-017: 2FA kurulmamış personel 2FA kurulum sayfasına yönlendirilir', function (): void {
    $staff = staffUser('admin');
    $staff->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();

    foreach (adminRouteCases() as $case) {
        $this->actingAs($staff)->call($case['method'], $case['url'])->assertRedirect(route('panel.security.two-factor'));
    }
});

it('FR-ADM-002: izni olmayan personel can: korumalı her rotada 403 alır', function (): void {
    // İzinsiz özel personel rolü: /admin'e girer ama hiçbir modüle erişemez.
    $role = Role::query()->create(['slug' => 'bos_personel', 'name' => 'Boş', 'priority' => 10, 'is_staff' => true]);
    $staff = staffUser();
    DB::table('user_roles')->insert(['user_id' => $staff->id, 'role_id' => $role->id, 'created_at' => now()]);
    $staff->flushAuthz();

    $this->actingAs($staff)->withSession(confirmedPassword())->get(route('admin.dashboard'))->assertOk();

    foreach (adminRouteCases() as $case) {
        if ($case['can'] === null) {
            continue;
        }
        $this->actingAs($staff)->withSession(confirmedPassword())->call($case['method'], $case['url'])
            ->assertForbidden();
    }
});

it('FR-ADM-002: süper admin her GET admin sayfasını görüntüleyebilir', function (): void {
    $super = staffUser('super_admin');

    $checked = 0;
    foreach (adminRouteCases() as $case) {
        if ($case['method'] === 'GET' && ! $case['missing']) {
            $response = $this->actingAs($super)->get($case['url']);
            expect($response->getStatusCode())->toBe(200, $case['route']->getName().' → '.$response->getStatusCode());
            $checked++;
        }
    }
    expect($checked)->toBeGreaterThan(50);
});
