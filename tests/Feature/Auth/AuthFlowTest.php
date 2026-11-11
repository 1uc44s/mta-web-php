<?php

declare(strict_types=1);

use App\Models\Consent;
use App\Models\User;
use App\Models\UserSecurityEvent;
use App\Notifications\TemplatedMailNotification;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]); // sızıntı kontrolü: temiz
    Notification::fake();
});

function registration(array $overrides = []): array
{
    return [
        'username' => 'yeni.oyuncu', 'email' => 'Yeni@Example.com',
        'password' => 'Gece-Vardiyasi-42', 'password_confirmation' => 'Gece-Vardiyasi-42',
        'terms' => '1', 'kvkk' => '1', ...$overrides,
    ];
}

/** Şablon anahtarıyla e-posta gönderildiğini doğrular ve e-postayı gerçekten render eder; düğme URL'ini döner. */
function assertMessage(User $user, string $key): string
{
    $url = '';
    Notification::assertSentTo($user, TemplatedMailNotification::class, function (TemplatedMailNotification $n) use ($user, $key, &$url): bool {
        if ($n->key !== $key) {
            return false;
        }
        $mail = $n->toMail($user);
        expect($mail->subject)->not->toBe('')->and($n->tries)->toBe(3)->and($n->backoff())->toBe([60, 300, 900]);
        $url = (string) $mail->actionUrl;

        return true;
    });

    return $url;
}

it('FR-NTF-005: doğrulama e-postasındaki bağlantı uçtan uca çalışır', function (): void {
    $this->post('/register', registration())->assertRedirect(route('register.check-email'));
    $user = User::query()->sole();

    $this->get(assertMessage($user, 'auth.verify_email'))->assertRedirect(route('login'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

// ---- Kayıt ---------------------------------------------------------------

it('FR-AUTH-001/004/005: kayıt kullanıcı + rıza oluşturur, doğrulama e-postası gönderir, otomatik giriş yapmaz', function (): void {
    $this->post('/register', registration())->assertRedirect(route('register.check-email'));

    $user = User::query()->sole();
    expect($user->email)->toBe('yeni@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and(password_get_info($user->password)['algoName'])->toBe('argon2id')
        ->and(Consent::query()->where('user_id', $user->id)->pluck('type')->sort()->values()->all())->toBe(['kvkk_ack', 'terms']);

    assertMessage($user, 'auth.verify_email');
    $this->assertGuest();
});

it('SEC-005: kayıtlı e-postayla yeniden kayıt aynı yanıtı verir, hesap sahibine bilgi gider', function (): void {
    $existing = User::factory()->create(['email' => 'yeni@example.com']);

    $this->post('/register', registration(['username' => 'baskasi']))->assertRedirect(route('register.check-email'));

    expect(User::query()->count())->toBe(1);
    assertMessage($existing, 'auth.already_registered');
    Notification::assertSentToTimes($existing, TemplatedMailNotification::class, 1);
});

it('FR-AUTH-001/002: geçersiz kayıt girdileri reddedilir', function (array $overrides, string $field): void {
    $this->post('/register', registration($overrides))->assertSessionHasErrors($field);
    expect(User::query()->count())->toBe(0);
})->with([
    'rezerv ad' => [['username' => 'Admin'], 'username'],
    'geçersiz karakter' => [['username' => 'çok kötü'], 'username'],
    'kısa ad' => [['username' => 'ab'], 'username'],
    'kısa parola' => [['password' => 'kisa', 'password_confirmation' => 'kisa'], 'password'],
    'parola kullanıcı adını içeriyor' => [['password' => 'yeni.oyuncu-2026!', 'password_confirmation' => 'yeni.oyuncu-2026!'], 'password'],
    'parola tekrarı uyuşmuyor' => [['password_confirmation' => 'Baska-Parola-99'], 'password'],
    'koşullar onaylanmadı' => [['terms' => null], 'terms'],
    'kvkk onaylanmadı' => [['kvkk' => null], 'kvkk'],
]);

it('FR-AUTH-001: kullanıcı adı büyük/küçük harf duyarsız benzersizdir', function (): void {
    User::factory()->create(['username' => 'Kaan']);

    $this->post('/register', registration(['username' => 'kaan']))->assertSessionHasErrors('username');
});

// ---- E-posta doğrulama ---------------------------------------------------

it('FR-AUTH-005: imzalı bağlantı oturumsuz doğrular ve girişe yönlendirir', function (): void {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['ulid' => $user->ulid, 'hash' => hash('sha256', $user->email)]);

    $this->get($url)->assertRedirect(route('login'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and(UserSecurityEvent::query()->where('event', 'email_verified')->count())->toBe(1);
});

it('FR-AUTH-005: imzasız, süresi dolmuş veya e-postası uyuşmayan bağlantı reddedilir', function (): void {
    $user = User::factory()->unverified()->create();

    $this->get(route('verification.verify', ['ulid' => $user->ulid, 'hash' => hash('sha256', $user->email)]))->assertForbidden();
    $this->get(URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['ulid' => $user->ulid, 'hash' => hash('sha256', $user->email)]))->assertForbidden();
    $this->get(URL::temporarySignedRoute('verification.verify', now()->addHour(), ['ulid' => $user->ulid, 'hash' => hash('sha256', 'baska@example.com')]))->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

// ---- Giriş --------------------------------------------------------------

it('FR-AUTH-007/009: kullanıcı adı veya e-posta ile giriş yapılır, olay kaydedilir', function (string $field): void {
    $user = User::factory()->create(['username' => 'kaan', 'email' => 'kaan@example.com']);

    $this->post('/login', ['login' => $field === 'username' ? 'Kaan' : 'KAAN@example.com', 'password' => UserFactory::PASSWORD])
        ->assertRedirect('/panel');

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull()
        ->and(UserSecurityEvent::query()->where(['user_id' => $user->id, 'event' => 'login_success'])->exists())->toBeTrue();
})->with(['username', 'email']);

it('FR-AUTH-010 / SEC-005: hatalı giriş genel mesaj verir ve maskeli olay kaydeder', function (): void {
    User::factory()->create(['username' => 'kaan']);

    $this->post('/login', ['login' => 'kaan', 'password' => 'yanlis-parola'])->assertSessionHasErrors(['login' => __('auth.failed')]);
    $this->post('/login', ['login' => 'yok-boyle-biri', 'password' => 'yanlis-parola'])->assertSessionHasErrors(['login' => __('auth.failed')]);

    $events = UserSecurityEvent::query()->where('event', 'login_failed')->get();
    expect($events)->toHaveCount(2)
        ->and($events->every(fn ($e) => strlen((string) $e->identifier_hash) === 64))->toBeTrue()
        ->and($events->every(fn ($e) => $e->ip_masked === '127.0.x.x'))->toBeTrue();
    $this->assertGuest();
});

it('FR-AUTH-008: aynı tanımlayıcı + IP için dakikada 5 hatalı denemeden sonra 429', function (): void {
    foreach (range(1, 5) as $i) {
        $this->post('/login', ['login' => 'kaan', 'password' => 'yanlis'])->assertStatus(302);
    }

    $this->post('/login', ['login' => 'kaan', 'password' => 'yanlis'])->assertStatus(429);
});

it('FR-AUTH-011: askıdaki hesap gerekçe ve bitiş tarihiyle reddedilir', function (): void {
    User::factory()->suspended(now()->addDay(), 'Kural ihlali RP-2')->create(['username' => 'kaan']);

    $this->post('/login', ['login' => 'kaan', 'password' => UserFactory::PASSWORD])
        ->assertSessionHasErrors('login');

    expect(session('errors')->first('login'))->toContain('Kural ihlali RP-2');
    $this->assertGuest();
});

it('FR-AUTH-011: süresi dolmuş askı girişte kalkar', function (): void {
    $user = User::factory()->suspended(now()->subMinute())->create(['username' => 'kaan']);

    $this->post('/login', ['login' => 'kaan', 'password' => UserFactory::PASSWORD])->assertRedirect('/panel');

    expect($user->fresh()->status->value)->toBe('active');
});

it('FR-AUTH-011: oturum açıkken askıya alınan kullanıcı sonraki istekte çıkarılır', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/panel')->assertOk();

    $user->forceFill(['status' => 'suspended', 'suspended_until' => null])->save();

    $this->get('/panel')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('panel girişsiz kullanıcıyı girişe yönlendirir, doğrulanmamışa uyarı gösterir', function (): void {
    $this->get('/panel')->assertRedirect(route('login'));

    $this->actingAs(User::factory()->unverified()->create())->get('/panel')
        ->assertOk()->assertSee(__('auth.ui.verify.resend'));
});

it('ARCH-6: çıkış oturumu sonlandırır ve tarayıcı cache’ini temizletir', function (): void {
    $this->actingAs(User::factory()->create())->post('/logout')
        ->assertRedirect(route('public.home'))
        ->assertHeader('Clear-Site-Data', '"cache"');

    $this->assertGuest();
});

// ---- Parola sıfırlama ----------------------------------------------------

it('FR-AUTH-013: bilinmeyen e-posta için de aynı genel yanıt verilir', function (): void {
    $this->post('/forgot-password', ['email' => 'kimse@example.com'])->assertSessionHas('status', __('passwords.sent'));
    $this->post('/forgot-password', ['email' => User::factory()->create()->email])->assertSessionHas('status', __('passwords.sent'));
});

it('FR-AUTH-013: sıfırlama yeni parola ayarlar, tüm oturumları siler ve bildirim gönderir', function (): void {
    $user = User::factory()->create();
    DB::table('sessions')->insert(['id' => 'eski-oturum', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    $token = Password::broker()->createToken($user);

    $this->post('/reset-password', [
        'token' => $token, 'email' => $user->email,
        'password' => 'Yepyeni-Parola-77', 'password_confirmation' => 'Yepyeni-Parola-77',
    ])->assertRedirect(route('login'));

    expect(password_verify('Yepyeni-Parola-77', $user->fresh()->password))->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
    assertMessage($user, 'security.password_changed');
});

it('FR-AUTH-013: sıfırlama isteği Türkçe bildirimle e-posta gönderir', function (): void {
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    assertMessage($user, 'auth.password_reset');
});

// ---- 2FA ------------------------------------------------------------------

it('FR-AUTH-016: 2FA açık kullanıcı parola sonrası doğrulama adımına yönlendirilir', function (): void {
    $user = User::factory()->create(['username' => 'kaan']);
    $user->forceFill([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => encrypt(json_encode(['kurtarma-kodu-1'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->post('/login', ['login' => 'kaan', 'password' => UserFactory::PASSWORD])->assertRedirect(route('two-factor.login'));
    $this->assertGuest();

    $this->post('/two-factor-challenge', ['recovery_code' => 'kurtarma-kodu-1'])->assertRedirect('/panel');
    $this->assertAuthenticatedAs($user);
});
