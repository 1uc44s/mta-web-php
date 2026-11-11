<?php

declare(strict_types=1);

it('SEC-024: CSP nonce tabanlıdır ve unsafe-inline / unsafe-eval içermez', function (): void {
    $csp = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');

    expect($csp)
        ->toMatch("/script-src 'self' 'nonce-[A-Za-z0-9+\\/=]+'/")
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'")
        ->toContain("base-uri 'self'")
        ->toContain("form-action 'self'")
        ->not->toContain('unsafe-eval')
        ->not->toContain('unsafe-inline');
});

it('SEC-025: güvenlik başlıkları her yanıtta bulunur (eşleşmeyen rotalar dahil)', function (string $uri, int $status): void {
    $this->get($uri)
        ->assertStatus($status)
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy');
})->with([
    'ana sayfa' => ['/', 200],
    'olmayan sayfa' => ['/bu-sayfa-yok', 404],
]);

it('SEC-025: HSTS yalnızca production + HTTPS isteklerinde gönderilir', function (): void {
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');
});
