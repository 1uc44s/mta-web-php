<?php

declare(strict_types=1);

use App\Actions\Account\AccountDeletion;
use App\Actions\Support\TicketWorkflow;
use App\Models\PushSubscription;
use App\Models\SupportCategory;
use App\Models\User;
use App\Notifications\PanelNotice;
use App\Services\Notifications\NotificationPreferences;
use App\Services\Push\PushTransport;
use App\Services\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** Sahte push iletimi: gönderilen yükleri kaydeder, ayarlanan HTTP kodunu döner. */
final class RecordingPushTransport implements PushTransport
{
    /** @var list<array{endpoint: string, payload: string}> */
    public array $sent = [];

    public int $status = 201;

    public function deliver(PushSubscription $subscription, string $payload): int
    {
        $this->sent[] = ['endpoint' => $subscription->endpoint, 'payload' => $payload];

        return $this->status;
    }
}

function enablePush(): RecordingPushTransport
{
    app(SettingsRepository::class)->set('features.push', true);
    config(['roleplay.push.public_key' => 'BPublicKeyForTests', 'roleplay.push.private_key' => 'PrivateKeyForTests']);
    $transport = new RecordingPushTransport;
    app()->instance(PushTransport::class, $transport);

    return $transport;
}

/** @return array<string, mixed> */
function pushPayload(string $suffix = 'a'): array
{
    return ['endpoint' => 'https://push.example.com/send/'.$suffix, 'keys' => ['p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8QcYP7DkM', 'auth' => 'tBHItJI5svbpez7KI4CCXg'], 'contentEncoding' => 'aes128gcm'];
}

it('push özelliği kapalıyken abonelik uçları 404', function (): void {
    $this->actingAs(User::factory()->create())->postJson(route('panel.push.subscribe'), pushPayload())->assertNotFound();
});

it('abonelik kaydedilir: auth anahtarı şifreli, endpoint tekil; HTTPS dışı reddedilir', function (): void {
    enablePush();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('panel.push.subscribe'), pushPayload())->assertCreated();
    $this->actingAs($user)->postJson(route('panel.push.subscribe'), pushPayload())->assertCreated();
    $this->actingAs($user)->postJson(route('panel.push.subscribe'), ['endpoint' => 'http://insecure.example.com/x'] + pushPayload())->assertUnprocessable();

    expect(PushSubscription::query()->count())->toBe(1);
    $raw = DB::table('push_subscriptions')->value('auth_token');
    expect($raw)->not->toBe('tBHItJI5svbpez7KI4CCXg')
        ->and(PushSubscription::query()->firstOrFail()->auth_token)->toBe('tBHItJI5svbpez7KI4CCXg');
});

it('kullanıcı başına abonelik sınırı uygulanır ve yalnızca kendi aboneliğini silebilir', function (): void {
    enablePush();
    config(['roleplay.push.max_subscriptions_per_user' => 2]);
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)->postJson(route('panel.push.subscribe'), pushPayload('1'))->assertCreated();
    $this->actingAs($user)->postJson(route('panel.push.subscribe'), pushPayload('2'))->assertCreated();
    $this->actingAs($user)->postJson(route('panel.push.subscribe'), pushPayload('3'))->assertUnprocessable();

    $this->actingAs($other)->postJson(route('panel.push.unsubscribe'), ['endpoint' => pushPayload('1')['endpoint']])->assertOk()->assertJsonPath('data.removed', false);
    $this->actingAs($user)->postJson(route('panel.push.unsubscribe'), ['endpoint' => pushPayload('1')['endpoint']])->assertOk()->assertJsonPath('data.removed', true);
    expect(PushSubscription::query()->count())->toBe(1);
});

it('panel bildirimi push ile gider; tercih kapalıysa gitmez; 410 aboneliği siler', function (): void {
    $transport = enablePush();
    $user = User::factory()->create();
    $this->actingAs($user)->postJson(route('panel.push.subscribe'), pushPayload())->assertCreated();

    $user->notify(new PanelNotice('application_approved', 'Başvurun onaylandı', route('panel.dashboard'), 'Polis başvurusu'));
    expect($transport->sent)->toHaveCount(1);
    $payload = json_decode($transport->sent[0]['payload'], true);
    expect($payload)->toMatchArray(['title' => 'Başvurun onaylandı', 'body' => 'Polis başvurusu', 'tag' => 'application_approved']);

    // Kategorisi olmayan tür (yetkili mutabakat uyarısı) push almaz.
    $user->notify(new PanelNotice('reconciliation_mismatch', 'Fark', route('panel.dashboard')));
    expect($transport->sent)->toHaveCount(1);

    $prefs = app(NotificationPreferences::class);
    $prefs->save($user, array_replace_recursive($prefs->matrix($user), ['applications' => ['push' => false]]));
    $user->notify(new PanelNotice('application_rejected', 'Reddedildi', route('panel.dashboard')));
    expect($transport->sent)->toHaveCount(1);

    $transport->status = 410;
    $user->notify(new PanelNotice('sanction_created', 'Ceza', route('panel.dashboard')));
    expect($transport->sent)->toHaveCount(2)->and(PushSubscription::query()->count())->toBe(0);
});

it('KVKK dökümü parola onayı ister; yalnızca kendi verisini ve public mesajları içerir', function (): void {
    $agent = staffUser('support_agent'); // roller + destek kategorileri
    $user = User::factory()->create();
    $other = User::factory()->create();
    $workflow = app(TicketWorkflow::class);
    $ticket = $workflow->create($user, SupportCategory::query()->firstOrFail(), 'Benim talebim', 'Oyuncu mesaj gövdesi burada.', (string) Str::uuid());
    $workflow->addStaffMessage($ticket, $agent, 'GIZLI-IC-NOT yalnızca personel', true);
    $workflow->create($other, SupportCategory::query()->firstOrFail(), 'Başkasının talebi', 'Başka kullanıcının mesajı.', (string) Str::uuid());

    $this->actingAs($user)->post(route('panel.account.export'))->assertRedirect();

    $response = $this->actingAs($user)->withSession(confirmedPassword())->post(route('panel.account.export'));
    $response->assertOk()->assertHeader('Content-Type', 'application/json; charset=utf-8');
    expect((string) $response->headers->get('Content-Disposition'))->toStartWith('attachment;')
        ->and((string) $response->headers->get('Cache-Control'))->toContain('no-store');

    $body = (string) $response->getContent();
    $data = json_decode($body, true);
    expect($data['profile']['email'])->toBe($user->email)
        ->and($data['support_tickets'])->toHaveCount(1)
        ->and($body)->toContain('Oyuncu mesaj gövdesi burada.')
        ->not->toContain('GIZLI-IC-NOT')
        ->not->toContain('Başkasının talebi')
        ->not->toContain($other->email)
        ->not->toContain((string) $user->password);

    expect(DB::table('user_security_events')->where(['user_id' => $user->id, 'event' => 'data_exported'])->count())->toBe(1);
});

it('parola onayı formu gerçek parola ile çalışır ve dökümü açar (regresyon: login sanal alanı)', function (): void {
    $user = User::factory()->create(['password' => 'Dogru-Parola-123!']);

    $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'yanlis-parola'])->assertSessionHasErrors('password');
    $this->actingAs($user)->post(route('password.confirm.store'), ['password' => 'Dogru-Parola-123!'])->assertSessionHasNoErrors()->assertRedirect();
    $this->actingAs($user)->post(route('panel.account.export'))->assertOk();
});

it('hesap anonimleştirmesi push aboneliklerini siler', function (): void {
    enablePush();
    $user = User::factory()->create();
    $this->actingAs($user)->postJson(route('panel.push.subscribe'), pushPayload())->assertCreated();

    app(AccountDeletion::class)->anonymize($user, null, 'test');

    expect(PushSubscription::query()->count())->toBe(0);
});
