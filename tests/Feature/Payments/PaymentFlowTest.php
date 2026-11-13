<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Models\PaymentEvent;
use App\Models\PaymentOrder;
use App\Models\PaymentPackage;
use App\Models\Refund;
use App\Models\User;
use App\Models\WalletAdjustmentRequest;
use App\Models\WalletReconciliationRun;
use App\Models\WalletTransaction;
use App\Services\Payments\FakeProvider;
use App\Services\Settings\SettingsRepository;
use App\Services\Wallet\LedgerService;
use App\Services\Wallet\WalletService;
use Database\Seeders\EconomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new EconomySeeder)->run();
    $settings = app(SettingsRepository::class);
    $settings->set('features.wallet', true);
    $settings->set('features.payments', true);
});

/** @param  array<string, mixed>  $extra */
function startOrder(User $user, ?int $packageId = null, array $extra = []): PaymentOrder
{
    $packageId ??= (int) PaymentPackage::query()->orderBy('sort')->value('id');
    test()->actingAs($user)->post(route('panel.payments.checkout'), ['package_id' => $packageId, 'idempotency_key' => (string) Str::uuid(), ...$extra])
        ->assertStatus(303);

    return PaymentOrder::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
}

function fakeWebhook(PaymentOrder $order, string $outcome = 'succeeded', ?string $body = null, ?string $signature = null): TestResponse
{
    $body ??= FakeProvider::eventBody($order, $outcome);

    return test()->call('POST', '/webhooks/payments/fake', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_FAKE_SIGNATURE' => $signature ?? FakeProvider::sign($body),
    ], $body);
}

it('FR-PAY-002: tutar her zaman sunucudaki paketten alınır; istemci tutarı yok sayılır', function (): void {
    $user = User::factory()->create();
    $package = PaymentPackage::query()->where('name', 'Standart')->firstOrFail();

    $order = startOrder($user, $package->id, ['amount_minor' => 1, 'credit_amount' => 999999]);

    expect($order->amount_minor)->toBe($package->price_minor)
        ->and($order->credit_amount)->toBe($package->credit_amount + $package->bonus_amount)
        ->and($order->status)->toBe('pending')
        ->and($order->order_number)->toMatch('/^MRP-\d{8}-[0-9A-Z]{4}$/');
});

it('FR-PAY-002: aynı form anahtarı ikinci kez yeni sipariş açmaz', function (): void {
    $user = User::factory()->create();
    $key = (string) Str::uuid();
    $package = PaymentPackage::query()->value('id');

    $this->actingAs($user)->post(route('panel.payments.checkout'), ['package_id' => $package, 'idempotency_key' => $key])->assertStatus(303);
    $this->actingAs($user)->post(route('panel.payments.checkout'), ['package_id' => $package, 'idempotency_key' => $key])->assertStatus(303);

    expect(PaymentOrder::query()->count())->toBe(1);
});

it('FR-PAY-003 + çıkış kriteri: aynı webhook iki kez gönderildiğinde tek kredi oluşur', function (): void {
    $user = User::factory()->create();
    $order = startOrder($user);
    $body = FakeProvider::eventBody($order, 'succeeded');

    fakeWebhook($order, body: $body)->assertOk()->assertJsonPath('duplicate', false);
    fakeWebhook($order, body: $body)->assertOk()->assertJsonPath('duplicate', true);
    // Farklı olay kimliğiyle ikinci "başarılı" olay da tek kredi üretir (ledger idempotency `pay:{ulid}` + durum kontrolü).
    fakeWebhook($order)->assertOk();

    expect($order->fresh()->status)->toBe('succeeded')
        ->and(WalletTransaction::query()->where('reason', 'topup')->count())->toBe(1)
        ->and(app(WalletService::class)->for($user)->balance)->toBe($order->credit_amount)
        ->and(PaymentEvent::query()->count())->toBe(2)
        ->and(DB::table('audit_logs')->where('action', AuditAction::PaymentsSucceeded->value)->count())->toBe(1)
        ->and($user->notifications()->count())->toBe(1);
});

it('FR-PAY-003: imzası geçersiz webhook 400 döner ve kaydedilmez', function (): void {
    $order = startOrder(User::factory()->create());

    fakeWebhook($order, signature: str_repeat('a', 64))->assertStatus(400);
    $this->postJson('/webhooks/payments/stripe', [])->assertStatus(400);

    expect(PaymentEvent::query()->count())->toBe(0)->and($order->fresh()->status)->toBe('pending');
});

it('FR-PAY-003: tutar veya para birimi eşleşmeyen olay bakiye yüklemez', function (): void {
    $user = User::factory()->create();
    $order = startOrder($user);
    $body = (string) json_encode(['id' => 'evt_x', 'type' => 'charge.succeeded', 'order_id' => $order->provider_order_id, 'amount' => 1, 'currency' => 'TRY']);

    fakeWebhook($order, body: $body)->assertOk();

    expect(PaymentEvent::query()->firstOrFail()->error)->toBe('amount_mismatch')
        ->and($order->fresh()->status)->toBe('pending')
        ->and(app(WalletService::class)->for($user)->balance)->toBe(0);
});

it('FR-PAY-004: dönüş sayfası bakiye yüklemez; durum JSON sahibine özel', function (): void {
    $user = User::factory()->create();
    $order = startOrder($user);

    $this->actingAs($user)->get(route('panel.payments.show', $order))->assertOk()->assertSee($order->order_number);
    $this->actingAs($user)->getJson(route('panel.payments.status', $order))->assertJsonPath('data.pending', true);
    expect(app(WalletService::class)->for($user)->balance)->toBe(0);

    $this->actingAs(User::factory()->create())->get(route('panel.payments.show', $order))->assertNotFound();
});

it('ROUTES §5.7: sahte ödeme ekranı imzalı URL ve sahiplik ister; aynı gelen kutusu yolundan işler', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('panel.payments.checkout'), ['package_id' => PaymentPackage::query()->value('id'), 'idempotency_key' => (string) Str::uuid()])
        ->assertStatus(303)->assertRedirectContains('/payments/fake/');
    $order = PaymentOrder::query()->firstOrFail();

    $this->actingAs($user)->get('/payments/fake/'.$order->ulid)->assertForbidden(); // imzasız
    $signed = URL::temporarySignedRoute('payments.fake.complete', now()->addMinutes(5), ['order' => $order->ulid]);
    $this->actingAs(User::factory()->create())->post($signed, ['outcome' => 'succeeded'])->assertNotFound();
    $this->actingAs($user)->post($signed, ['outcome' => 'succeeded'])->assertRedirect(route('panel.payments.show', $order));

    expect($order->fresh()->status)->toBe('succeeded');
});

it('FR-PAY-006: ödeme modülü kapalıyken tüm ödeme rotaları 404', function (): void {
    app(SettingsRepository::class)->set('features.payments', false);
    $user = User::factory()->create();

    $this->actingAs($user)->get('/panel/top-up')->assertNotFound();
    $this->actingAs($user)->post('/panel/payments/checkout', ['package_id' => 1, 'idempotency_key' => (string) Str::uuid()])->assertNotFound();
    $this->postJson('/webhooks/payments/fake', [])->assertNotFound();
});

it('FR-WAL-006: eşik üstü düzeltme farklı bir onaylayıcı ister; eşik altı kendi onayıyla uygulanabilir', function (): void {
    $finance = staffUser('finance_manager');
    $other = staffUser('finance_manager');
    $player = User::factory()->create();

    $this->actingAs($finance)->withSession(confirmedPassword())->post(route('admin.economy.adjustments.store'), [
        'username' => $player->username, 'direction' => 'credit', 'amount' => 1000, 'reason' => 'Etkinlik ödülü — sunucu turnuvası birinciliği',
    ])->assertRedirect();
    $big = WalletAdjustmentRequest::query()->latest('id')->firstOrFail();
    expect($big->requires_second_approval)->toBeTrue();

    $this->actingAs($finance)->withSession(confirmedPassword())->post(route('admin.economy.adjustments.approve', $big->ulid))->assertSessionHasErrors('review_note');
    $this->actingAs($other)->withSession(confirmedPassword())->post(route('admin.economy.adjustments.approve', $big->ulid))->assertRedirect();

    $this->actingAs($finance)->withSession(confirmedPassword())->post(route('admin.economy.adjustments.store'), [
        'username' => $player->username, 'direction' => 'debit', 'amount' => 100, 'reason' => 'Mükerrer ödül düzeltmesi yapılıyor',
    ]);
    $small = WalletAdjustmentRequest::query()->latest('id')->firstOrFail();
    $this->actingAs($finance)->withSession(confirmedPassword())->post(route('admin.economy.adjustments.approve', $small->ulid))->assertRedirect();

    expect(app(WalletService::class)->for($player)->balance)->toBe(900)
        ->and(DB::table('audit_logs')->where('action', AuditAction::WalletAdjustmentApplied->value)->count())->toBe(2);
});

it('FR-WAL-003: ters kayıt talebi dört göz akışından geçer ve bir kez uygulanır', function (): void {
    $player = User::factory()->create();
    $wallet = app(WalletService::class)->for($player);
    $original = app(LedgerService::class)->post($wallet->id, 'credit', 2000, 'promo', 'promo:1', 'Hatalı promosyon');
    [$a, $b] = [staffUser('finance_manager'), staffUser('finance_manager')];

    $this->actingAs($a)->withSession(confirmedPassword())->post(route('admin.economy.reversal-request', $original->ulid), ['reason' => 'Promosyon yanlış hesaba tanımlandı'])->assertRedirect();
    $request = WalletAdjustmentRequest::query()->firstOrFail();
    $this->actingAs($b)->withSession(confirmedPassword())->post(route('admin.economy.adjustments.approve', $request->ulid))->assertRedirect();

    expect($wallet->fresh()->balance)->toBe(0)
        ->and(WalletTransaction::query()->where('reversal_of_id', $original->id)->count())->toBe(1)
        ->and(DB::table('audit_logs')->where('action', AuditAction::WalletReversalApplied->value)->count())->toBe(1);

    $this->actingAs($a)->withSession(confirmedPassword())->post(route('admin.economy.reversal-request', $original->ulid), ['reason' => 'Tekrar ters çevirme denemesi'])->assertSessionHasErrors('reason');
});

it('FR-PAY-005: iade dört göz ister; başarılıysa kredi düşülür; bakiye yetmiyorsa inceleme', function (): void {
    $player = User::factory()->create();
    $order = startOrder($player);
    fakeWebhook($order)->assertOk();
    [$a, $b] = [staffUser('finance_manager'), staffUser('finance_manager')];

    $this->actingAs($a)->withSession(confirmedPassword())->post(route('admin.payments.refund-request', $order), ['amount_minor' => $order->amount_minor, 'reason' => 'Oyuncu yanlış paket aldı'])->assertRedirect();
    $refund = Refund::query()->firstOrFail();
    $this->actingAs($a)->withSession(confirmedPassword())->post(route('admin.refunds.approve', $refund->ulid))->assertSessionHasErrors('refund');
    $this->actingAs($b)->withSession(confirmedPassword())->post(route('admin.refunds.approve', $refund->ulid))->assertRedirect();

    expect($refund->fresh()->status)->toBe('succeeded')->and($order->fresh()->status)->toBe('refunded')
        ->and(app(WalletService::class)->for($player)->balance)->toBe(0)
        ->and(DB::table('audit_logs')->where('action', AuditAction::RefundsCompleted->value)->count())->toBe(1);

    // Kredi harcanmışsa: requires_review, sağlayıcı çağrılmaz.
    $order2 = startOrder($player);
    fakeWebhook($order2)->assertOk();
    app(LedgerService::class)->post(app(WalletService::class)->for($player)->id, 'debit', 50, 'purchase', 'buy:1', 'Satın alma');
    $this->actingAs($a)->withSession(confirmedPassword())->post(route('admin.payments.refund-request', $order2), ['amount_minor' => $order2->amount_minor, 'reason' => 'Oyuncu iade istedi']);
    $refund2 = Refund::query()->latest('id')->firstOrFail();
    $this->actingAs($b)->withSession(confirmedPassword())->post(route('admin.refunds.approve', $refund2->ulid));

    expect($refund2->fresh()->status)->toBe('requires_review')->and($order2->fresh()->status)->toBe('succeeded');
});

it('FR-WAL-007: mutabakat ledger toplamı ile bakiyeyi karşılaştırır, farkı kritik audit ile bildirir', function (): void {
    $player = User::factory()->create();
    $order = startOrder($player);
    fakeWebhook($order)->assertOk();

    $this->artisan('wallet:reconcile')->assertSuccessful();
    expect(WalletReconciliationRun::query()->latest('id')->firstOrFail()->mismatches)->toBe(0);

    // Bakiyeye ledger dışından yazım (simülasyon) → fark
    DB::table('wallets')->where('user_id', $player->id)->update(['balance' => DB::raw('balance + 7')]);
    $this->artisan('wallet:reconcile')->assertFailed();

    expect(WalletReconciliationRun::query()->latest('id')->firstOrFail()->mismatches)->toBe(1)
        ->and(DB::table('audit_logs')->where('action', AuditAction::WalletReconciliationMismatch->value)->where('severity', 'critical')->count())->toBe(1);
});

it('Çıkış kriteri: finans yöneticisi rol yönetemez; destek yetkilisi finans uçlarına erişemez', function (): void {
    $finance = staffUser('finance_manager');
    $support = staffUser('support_agent');

    $this->actingAs($finance)->get(route('admin.roles.index'))->assertForbidden();
    $this->actingAs($finance)->get(route('admin.economy.index'))->assertOk();

    foreach (['admin.economy.index', 'admin.economy.transactions', 'admin.economy.adjustments.index', 'admin.payments.index', 'admin.payments.reconciliation'] as $route) {
        $this->actingAs($support)->get(route($route))->assertForbidden();
    }
});

it('FR-WAL-001: oyuncu cüzdanı ve işlemleri yalnızca kendisininkini gösterir', function (): void {
    $player = User::factory()->create();
    $other = User::factory()->create();
    app(LedgerService::class)->post(app(WalletService::class)->for($other)->id, 'credit', 77, 'promo', 'o:1', 'BASKASININ-KAYDI');
    app(LedgerService::class)->post(app(WalletService::class)->for($player)->id, 'credit', 33, 'promo', 'p:1', 'Benim kaydım');

    $this->actingAs($player)->get(route('panel.wallet'))->assertOk()->assertSee('Benim kaydım')->assertDontSee('BASKASININ-KAYDI');
    $this->actingAs($player)->get(route('panel.transactions'))->assertOk()->assertDontSee('BASKASININ-KAYDI');
});
