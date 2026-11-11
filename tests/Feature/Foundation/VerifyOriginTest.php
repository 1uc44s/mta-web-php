<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('web')->post('/_test/origin', fn () => response('ok'));

    // Bu testler yalnızca Origin katmanını sınar; CSRF token katmanı ayrıca test edilir.
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

it('RULES-7: cross-site POST (Sec-Fetch-Site) 419 ile reddedilir', function (string $site): void {
    $this->post('/_test/origin', [], ['Sec-Fetch-Site' => $site])->assertStatus(419);
})->with(['cross-site', 'same-site']);

it('RULES-7: same-origin POST kabul edilir', function (): void {
    $this->post('/_test/origin', [], ['Sec-Fetch-Site' => 'same-origin'])->assertOk();
});

it('RULES-7: Fetch Metadata yokken yabancı Origin 419 ile reddedilir', function (): void {
    $this->post('/_test/origin', [], ['Origin' => 'https://evil.example'])->assertStatus(419);
});

it('RULES-7: Fetch Metadata yokken aynı Origin kabul edilir', function (): void {
    $this->post('/_test/origin', [], ['Origin' => rtrim((string) config('app.url'), '/')])->assertOk();
});

// Not: Laravel'in ValidateCsrfToken'ı birim testlerde kendini atlar (runningUnitTests). Token'sız POST → 419
// kontrolü bu yüzden E2E / Faz 11 güvenlik testlerinde yapılır (docs/TODO.md §4.3).
