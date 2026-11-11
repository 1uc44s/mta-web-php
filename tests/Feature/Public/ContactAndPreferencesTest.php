<?php

declare(strict_types=1);

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

function contactPayload(array $overrides = []): array
{
    return [
        'name' => 'Deniz', 'email' => 'Deniz@Example.com', 'subject' => 'İş birliği',
        'body' => 'Merhaba, etkinlik için iş birliği yapmak istiyoruz.',
        'form_token' => Crypt::encryptString((string) now()->subSeconds(10)->getTimestamp()),
        ...$overrides,
    ];
}

it('FR-PUB-013: geçerli iletişim mesajı kaydedilir, e-posta normalize edilir, IP hashlenir', function (): void {
    $this->post('/contact', contactPayload())->assertStatus(303)->assertSessionHas('toast');

    $message = ContactMessage::query()->sole();
    expect($message->email)->toBe('deniz@example.com')
        ->and($message->ip_hash)->toHaveLength(64)
        ->and($message->status)->toBe('new');
});

it('FR-PUB-013: honeypot dolu ise doğrulama hatası verir, kayıt oluşmaz', function (): void {
    $this->post('/contact', contactPayload(['website' => 'http://spam.example']))->assertSessionHasErrors('website');

    expect(ContactMessage::query()->count())->toBe(0);
});

it('FR-PUB-013: 3 sn’den hızlı gönderim sessizce yutulur', function (): void {
    $this->post('/contact', contactPayload(['form_token' => Crypt::encryptString((string) now()->getTimestamp())]))
        ->assertStatus(303);

    expect(ContactMessage::query()->count())->toBe(0);
});

it('FR-PUB-013: doğrulama hataları Türkçe mesajla döner', function (): void {
    $this->post('/contact', contactPayload(['body' => 'kısa']))
        ->assertSessionHasErrors(['body' => 'Mesajın en az 20 karakter olmalı.']);
});

it('SRS-5.4: iletişim formu saatte 5 istekle sınırlıdır', function (): void {
    foreach (range(1, 5) as $i) {
        $this->post('/contact', contactPayload())->assertStatus(303);
    }
    $this->post('/contact', contactPayload())->assertStatus(429);
});

it('US-PUB-11: tema tercihi cookie’ye yazılır ve sonraki istekte HTML’e render edilir', function (): void {
    $this->post('/preferences/theme', ['theme' => 'light', 'effects' => 'low', 'return' => '/rules'])
        ->assertRedirect('/rules')
        ->assertPlainCookie('mrp_theme', 'light')
        ->assertPlainCookie('mrp_effects', 'low');

    $this->withUnencryptedCookies(['mrp_theme' => 'light', 'mrp_effects' => 'low'])->get('/about')
        ->assertSee('data-theme="light"', false)
        ->assertSee('data-effects="low"', false);
});

it('SEC-026: tema formu dış adrese yönlendirmez', function (): void {
    $this->post('/preferences/theme', ['theme' => 'dark', 'return' => 'https://evil.example'])->assertRedirect(route('public.home'));
});

it('US-PUB-11: geçersiz tema değeri reddedilir', function (): void {
    $this->post('/preferences/theme', ['theme' => '"><script>'])->assertSessionHasErrors('theme');
});

it('FR-PUB-020: çerez onayı kaydedilir ve banner bir daha gösterilmez', function (): void {
    $this->post('/cookie-consent', ['analytics' => '0'])->assertPlainCookie('mrp_consent', 'essential');

    $this->get('/')->assertSee('data-cookie-banner', false);
    $this->withUnencryptedCookies(['mrp_consent' => 'essential'])->get('/')->assertDontSee('data-cookie-banner', false);
});
