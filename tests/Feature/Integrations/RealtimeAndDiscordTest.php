<?php

declare(strict_types=1);

use App\Actions\Content\ContentWorkflow;
use App\Actions\Support\TicketWorkflow;
use App\Events\SupportMessagePosted;
use App\Events\SupportQueueChanged;
use App\Models\Announcement;
use App\Models\MessageDispatch;
use App\Models\SupportCategory;
use App\Models\User;
use App\Notifications\PanelNotice;
use App\Services\Integrations\DiscordWebhook;
use App\Services\Integrations\OpsAlerts;
use App\Services\Settings\SettingsRepository;
use App\Support\Realtime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

const DISCORD_HOOK = 'https://discord.com/api/webhooks/123456/abcDEF-ghi_JKL';

/** Reverb'i test için açar ve kanal tanımlarını reverb sürücüsüne yeniden kaydeder. */
function enableRealtime(): void
{
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => '1',
        'broadcasting.connections.reverb.options.host' => 'ws.example.test',
        'broadcasting.connections.reverb.options.port' => 443,
        'broadcasting.connections.reverb.options.scheme' => 'https',
    ]);
    require base_path('routes/channels.php');
}

function channelAuth(User $user, string $channel): TestResponse
{
    return test()->actingAs($user)->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-'.$channel]);
}

it('Reverb kapalıyken meta etiketi, CSP ws kaynağı ve broadcast kanalı yoktur', function (): void {
    $user = User::factory()->create();

    expect(Realtime::enabled())->toBeFalse()
        ->and((new PanelNotice('x', 'Başlık', '/'))->via($user))->not->toContain('broadcast');

    $response = $this->actingAs($user)->get(route('panel.dashboard'));
    $response->assertOk()->assertDontSee('mrp-realtime', false);
    expect((string) $response->headers->get('Content-Security-Policy'))->not->toContain('wss://');
});

it('Reverb açıkken meta yalnızca public anahtarı içerir ve CSP wss host ekler', function (): void {
    enableRealtime();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('panel.dashboard'));
    $response->assertOk()->assertSee('mrp-realtime', false)->assertSee('test-key', false)->assertDontSee('test-secret', false);
    expect((string) $response->headers->get('Content-Security-Policy'))->toContain('wss://ws.example.test:443')
        ->and((new PanelNotice('x', 'Başlık', '/'))->via($user))->toContain('broadcast');
});

it('kanal yetkisi: yalnızca kendi kullanıcı kanalı ve kendi talebi; branş kanalı yalnızca branş personeline', function (): void {
    enableRealtime();
    Event::fake([SupportMessagePosted::class, SupportQueueChanged::class]);
    $agent = staffUser('support_agent');
    $user = User::factory()->create();
    $other = User::factory()->create();
    $ticket = app(TicketWorkflow::class)->create($user, SupportCategory::query()->firstOrFail(), 'Talep', 'Talep açıklaması burada yazıyor.', (string) Str::uuid());

    channelAuth($user, 'user.'.$user->id)->assertOk();
    channelAuth($user, 'user.'.$other->id)->assertForbidden();
    channelAuth($user, 'support.ticket.'.$ticket->ulid)->assertOk();
    channelAuth($other, 'support.ticket.'.$ticket->ulid)->assertForbidden();
    channelAuth($user, 'staff.branch.'.$ticket->branch_id)->assertForbidden();

    $canSeeBranch = in_array($ticket->branch_id, $agent->activeBranchIds(), true) || $agent->can('support.all_branches');
    channelAuth($agent, 'staff.branch.'.$ticket->branch_id)->assertStatus($canSeeBranch ? 200 : 403);
});

it('destek olayları yalnızca kimlik alanı yayınlar, mesaj gövdesi yayınlanmaz', function (): void {
    enableRealtime();
    Event::fake([SupportMessagePosted::class, SupportQueueChanged::class]);
    staffUser();
    $user = User::factory()->create();
    $workflow = app(TicketWorkflow::class);
    $ticket = $workflow->create($user, SupportCategory::query()->firstOrFail(), 'Talep', 'GIZLI-GOVDE ilk mesaj metni.', (string) Str::uuid());
    $workflow->addPlayerMessage($ticket, $user, 'GIZLI-GOVDE ikinci mesaj.');

    Event::assertDispatched(SupportMessagePosted::class, function (SupportMessagePosted $e) use ($ticket): bool {
        return $e->broadcastWith() === ['ticket' => $ticket->ulid, 'author' => 'player']
            && $e->broadcastOn()[0]->name === 'private-support.ticket.'.$ticket->ulid;
    });
    Event::assertDispatched(SupportQueueChanged::class, fn (SupportQueueChanged $e): bool => $e->broadcastWith() === ['branch' => $ticket->branch_id]);
});

it('Discord metni markdown ve toplu bahsetmelerden arındırılır; 2000 karakterle sınırlanır', function (): void {
    expect(DiscordWebhook::sanitize('**kalın** @everyone @here <@123> `kod` > alıntı'))
        ->toBe("\\*\\*kalın\\*\\* @\u{200B}everyone @\u{200B}here <\u{200B}@123\\> \\`kod\\` \\> alıntı");

    $payload = DiscordWebhook::payload(str_repeat('a', 2500));
    expect(mb_strlen($payload['content']))->toBe(2000)->and($payload['allowed_mentions'])->toBe(['parse' => []]);
});

it('Discord webhook URL\'si yalnızca discord.com webhook biçiminde kabul edilir', function (): void {
    $discord = app(DiscordWebhook::class);

    config(['roleplay.discord.webhooks.ops' => 'https://evil.example.com/api/webhooks/1/x']);
    expect($discord->url('ops'))->toBeNull();

    config(['roleplay.discord.webhooks.ops' => DISCORD_HOOK]);
    expect($discord->url('ops'))->toBe(DISCORD_HOOK);
});

it('herkese açık duyuru Discord kanalına bir kez gider; rol hedefli duyuru gitmez', function (): void {
    Http::fake(['discord.com/*' => Http::response(null, 204)]);
    config(['roleplay.discord.webhooks.announcements' => DISCORD_HOOK]);
    app(SettingsRepository::class)->set('discord.announcements', true);
    $admin = staffUser('super_admin');

    $public = Announcement::query()->create(['title' => 'Bakım @everyone', 'body' => 'Metin', 'channels' => ['panel', 'discord'], 'audience' => 'all', 'starts_at' => now()])->refresh();
    app(ContentWorkflow::class)->apply('publish', $public, $admin);
    app(ContentWorkflow::class)->apply('unpublish', $public->fresh(), $admin);
    app(ContentWorkflow::class)->apply('publish', $public->fresh(), $admin);

    $roles = Announcement::query()->create(['title' => 'Yetkili duyurusu', 'body' => 'Metin', 'channels' => ['discord'], 'audience' => 'roles', 'audience_role_ids' => [1], 'starts_at' => now()])->refresh();
    app(ContentWorkflow::class)->apply('publish', $roles, $admin);

    Http::assertSentCount(1);
    Http::assertSent(fn (HttpRequest $r): bool => $r->url() === DISCORD_HOOK
        && str_contains((string) $r['content'], "@\u{200B}everyone")
        && $r['allowed_mentions'] === ['parse' => []]);
    expect(MessageDispatch::query()->where(['channel' => 'discord_webhook', 'status' => 'sent'])->count())->toBe(1);
});

it('ops uyarısı ayar kapalıyken gitmez; açıkken aynı kontrol için bir kez gider', function (): void {
    Http::fake(['discord.com/*' => Http::response(null, 204)]);
    config(['roleplay.discord.webhooks.ops' => DISCORD_HOOK]);
    $ops = app(OpsAlerts::class);

    $ops->critical('health.disk_free_ratio', ['value' => 0.05]);
    Http::assertNothingSent();

    app(SettingsRepository::class)->set('discord.ops_alerts', true);
    $ops->critical('health.disk_free_ratio', ['value' => 0.05]);
    $ops->critical('health.disk_free_ratio', ['value' => 0.04]);

    Http::assertSentCount(1);
});

it('Discord hata yanıtı gönderim kaydını failed yapar', function (): void {
    Http::fake(['discord.com/*' => Http::response(['message' => 'Unknown Webhook'], 404)]);
    config(['roleplay.discord.webhooks.ops' => DISCORD_HOOK]);
    app(SettingsRepository::class)->set('discord.ops_alerts', true);

    app(OpsAlerts::class)->critical('wallet.reconciliation_mismatch', ['mismatches' => 1]);

    expect(MessageDispatch::query()->where('channel', 'discord_webhook')->value('status'))->toBe('failed')
        ->and(MessageDispatch::query()->where('channel', 'discord_webhook')->value('error'))->toBe('http_404');
});
