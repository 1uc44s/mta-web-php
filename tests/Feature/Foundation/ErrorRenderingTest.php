<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('FR-PUB-017: olmayan sayfa Türkçe 404 sayfası ve istek kimliği gösterir', function (): void {
    $response = $this->get('/bu-sayfa-yok')->assertNotFound();

    $response->assertSee(__('errors.pages.404.title'))
        ->assertSee($response->headers->get('X-Request-Id'))
        ->assertSee('noindex, nofollow', false);
});

it('SRS-8: JSON istekte 404 standart hata biçiminde döner', function (): void {
    $response = $this->getJson('/api/v1/bu-uc-yok')->assertNotFound();

    $response->assertExactJson([
        'success' => false,
        'error' => ['code' => 'NOT_FOUND', 'message' => __('errors.api.NOT_FOUND')],
        'requestId' => $response->headers->get('X-Request-Id'),
    ]);
});

it('SRS-8: doğrulama hataları alan bazlı details listesiyle döner', function (): void {
    Route::post('/api/v1/_test/validate', fn (Request $request) => $request->validate([
        'code' => ['required', 'string', 'size:8'],
    ]));

    $this->postJson('/api/v1/_test/validate', ['code' => 'abc'])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.details.0.field', 'code');
});

it('SRS-8: sunucu hatası iç ayrıntı sızdırmaz (JSON)', function (): void {
    config(['app.debug' => false]);
    Route::get('/api/v1/_test/boom', fn () => throw new RuntimeException('SELECT * FROM gizli_tablo WHERE secret=1'));

    $response = $this->getJson('/api/v1/_test/boom')->assertStatus(500);

    $response->assertJsonPath('error.code', 'SERVER_ERROR');
    expect($response->getContent())->not->toContain('gizli_tablo')->not->toContain('RuntimeException');
});

it('SRS-8: sunucu hatası iç ayrıntı sızdırmaz (HTML)', function (): void {
    config(['app.debug' => false]);
    Route::get('/_test/boom', fn () => throw new RuntimeException('C:\\xampp\\gizli\\yol.php'));

    $response = $this->get('/_test/boom')->assertStatus(500);

    $response->assertSee(__('errors.pages.500.title'));
    expect($response->getContent())->not->toContain('gizli')->not->toContain('RuntimeException');
});
