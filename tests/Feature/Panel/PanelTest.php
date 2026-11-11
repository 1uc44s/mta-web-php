<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportActivity;
use App\Notifications\TemplatedMailNotification;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/* Faz 6 — docs/SRS.md FR-UCP-002..007, FR-AUTH-014..020, FR-PWA-001..005. */

uses(RefreshDatabase::class);

beforeEach(function (): void {
    staffUser();
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
});

it('FR-UCP-007: panel yanıtları noindex + no-store taşır', function (): void {
    $this->actingAs(User::factory()->create())->get('/panel')->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeaderContains('Cache-Control', 'no-store');
});

it('FR-UCP-002/003: bildirim sayacı, ETag (304) ve sahiplik (gerçek database kanalı)', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $ticket = SupportTicket::query()->make(['number' => 'T-000009', 'subject' => 'Konu', 'ulid' => strtolower((string) Str::ulid())]);
    $user->notify(new SupportActivity($ticket, 'staff_replied'));
    $other->notify(new SupportActivity($ticket, 'staff_replied'));

    $response = $this->actingAs($user)->getJson(route('panel.notifications.count'))->assertOk()->assertJsonPath('data.unread', 1);
    $this->actingAs($user)->get(route('panel.notifications.count'), ['If-None-Match' => (string) $response->headers->get('ETag')])->assertStatus(304);

    $this->actingAs($user)->post(route('panel.notifications.read', $other->notifications()->value('id')))->assertNotFound();

    $this->actingAs($user)->post(route('panel.notifications.read-all'));
    expect($user->unreadNotifications()->count())->toBe(0)->and($other->unreadNotifications()->count())->toBe(1);
    $this->actingAs($user)->get(route('panel.notifications.index'))->assertOk()->assertSee('T-000009');
});

it('FR-UCP-004: tema ve düşük efekt tercihi kullanıcıya ve cookie’ye yazılır', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('panel.settings.preferences'), ['theme' => 'light', 'reduced_effects' => '1'])
        ->assertCookie('mrp_theme', 'light', false)->assertCookie('mrp_effects', 'low', false);

    expect($user->fresh()->theme)->toBe('light')->and($user->fresh()->reduced_effects)->toBeTrue();
    $this->actingAs($user)->post(route('panel.settings.preferences'), ['theme' => 'neon'])->assertSessionHasErrors('theme');
});

it('FR-AUTH-019: parola değişimi mevcut parolayı ister, diğer oturumları kapatır, güvenlik e-postası gönderir', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    DB::table('sessions')->insert(['id' => str_repeat('a', 40), 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

    $this->actingAs($user)->post(route('panel.security.password'), ['current_password' => 'yanlis', 'password' => 'Yeni-Gece-Parola-55', 'password_confirmation' => 'Yeni-Gece-Parola-55'])
        ->assertSessionHasErrors('current_password');

    $this->actingAs($user)->post(route('panel.security.password'), ['current_password' => UserFactory::PASSWORD, 'password' => 'Yeni-Gece-Parola-55', 'password_confirmation' => 'Yeni-Gece-Parola-55'])
        ->assertSessionHasNoErrors();

    expect(password_verify('Yeni-Gece-Parola-55', $user->fresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('id', str_repeat('a', 40))->exists())->toBeFalse();
    Notification::assertSentTo($user, TemplatedMailNotification::class, fn ($n) => $n->key === 'security.password_changed');
});

it('FR-AUTH-018: e-posta değişikliği yeni adrese onay gönderir, onayla uygulanır, eski adrese bilgi gider', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'eski@example.com']);

    $this->actingAs($user)->withSession(confirmedPassword())->post(route('panel.security.email'), ['email' => 'Yeni@Example.com'])->assertSessionHasNoErrors();

    $token = null;
    Notification::assertSentTo(new AnonymousNotifiable, TemplatedMailNotification::class, function ($n, $channels, $notifiable) use (&$token): bool {
        if ($n->key !== 'security.email_change_requested' || ($notifiable->routes['mail'] ?? null) !== 'yeni@example.com') {
            return false;
        }
        $token = basename((string) $n->context['link.confirm']);

        return true;
    });

    $this->actingAs($user)->get(route('panel.security.email.confirm', $token))->assertOk()->assertSee('y***@e***.com');
    expect($user->fresh()->email)->toBe('eski@example.com'); // GET değiştirmez

    $this->actingAs($user)->post(route('panel.security.email.confirm.store', $token))->assertRedirect(route('panel.security'));
    expect($user->fresh()->email)->toBe('yeni@example.com');
    Notification::assertSentTo(new AnonymousNotifiable, TemplatedMailNotification::class,
        fn ($n, $c, $notifiable) => $n->key === 'security.email_changed_notice' && ($notifiable->routes['mail'] ?? null) === 'eski@example.com');

    $this->actingAs($user)->post(route('panel.security.email.confirm.store', $token))->assertSessionHasErrors('token'); // tek kullanım
});

it('FR-AUTH-014/015: oturum listesi maskeli IP gösterir; kapatma parola onayı ister ve sahipliği kontrol eder', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    DB::table('sessions')->insert([
        ['id' => str_repeat('b', 40), 'user_id' => $user->id, 'ip_address' => '85.105.20.30', 'user_agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/120', 'payload' => '', 'last_activity' => time()],
        ['id' => str_repeat('c', 40), 'user_id' => $other->id, 'ip_address' => '1.2.3.4', 'user_agent' => '', 'payload' => '', 'last_activity' => time()],
    ]);

    $this->actingAs($user)->get(route('panel.security'))->assertOk()->assertSee('85.105.x.x')->assertDontSee('85.105.20.30');
    $this->actingAs($user)->post(route('panel.security.sessions.revoke', str_repeat('b', 40)))->assertRedirect(route('password.confirm'));
    $this->actingAs($user)->withSession(confirmedPassword())->post(route('panel.security.sessions.revoke', str_repeat('c', 40)))->assertNotFound();
    $this->actingAs($user)->withSession(confirmedPassword())->post(route('panel.security.sessions.revoke', str_repeat('b', 40)))->assertRedirect();

    expect(DB::table('sessions')->where('id', str_repeat('b', 40))->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', str_repeat('c', 40))->exists())->toBeTrue();
});

it('FR-AUTH-020 / DB-13: hesap silme talebi 14 gün sonra anonimleştirilir; iptal edilebilir; personel talep edemez', function (): void {
    $user = User::factory()->create(['username' => 'silinecek', 'email' => 'silinecek@example.com']);

    $this->actingAs($user)->withSession(confirmedPassword())->post(route('panel.account.delete-request'))->assertSessionHasNoErrors();
    expect($user->fresh()->status)->toBe(UserStatus::PendingDeletion);

    $this->actingAs($user)->post(route('panel.account.delete-cancel'));
    expect($user->fresh()->status)->toBe(UserStatus::Active);

    $this->actingAs($user)->withSession(confirmedPassword())->post(route('panel.account.delete-request'));
    $this->travel(15)->days();
    $this->artisan('mrp:maintenance-tick')->assertSuccessful();

    $fresh = $user->fresh();
    expect($fresh->status)->toBe(UserStatus::Anonymized)
        ->and($fresh->username)->toBe('silinmis_'.$user->id)
        ->and($fresh->email)->toEndWith('@invalid.local')
        ->and(DB::table('user_security_events')->where('user_id', $user->id)->count())->toBe(0)
        ->and(AuditLog::query()->where('action', 'users.anonymized')->value('actor_type'))->toBe('scheduler');

    $this->actingAs(staffUser('moderator'))->withSession(confirmedPassword())->post(route('panel.account.delete-request'))->assertSessionHasErrors('account');
});

it('ROUTES-6.2: yetkili KVKK talebiyle hesabı anonimleştirir (gerekçe + parola onayı)', function (): void {
    $player = User::factory()->create();
    $admin = staffUser('admin');

    $this->actingAs($admin)->withSession(confirmedPassword())->post(route('admin.users.anonymize', $player), ['reason' => ''])->assertSessionHasErrors('reason');
    $this->actingAs($admin)->withSession(confirmedPassword())->post(route('admin.users.anonymize', $player), ['reason' => 'KVKK silme talebi #12'])->assertSessionHasNoErrors();

    expect($player->fresh()->status)->toBe(UserStatus::Anonymized);
});

it('FR-PWA-001/003: manifest geçerli; SW panel/admin/api yollarını asla cache’lemez', function (): void {
    $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);
    expect($manifest['start_url'])->toBe('/panel?source=pwa')
        ->and($manifest['display'])->toBe('standalone')
        ->and(collect($manifest['icons'])->pluck('purpose')->all())->toContain('maskable');

    foreach ($manifest['icons'] as $icon) {
        expect(file_exists(public_path(ltrim($icon['src'], '/'))))->toBeTrue($icon['src']);
    }

    $sw = (string) file_get_contents(public_path('sw.js'));
    expect($sw)->toContain('panel|admin|api')->toContain('CLEAR_RUNTIME_CACHE')->toContain('SKIP_WAITING')->toContain('/offline.html')
        ->and(file_exists(public_path('offline.html')))->toBeTrue();
});
