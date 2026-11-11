<?php

declare(strict_types=1);

it('NFR-OBS-001: her yanıta ULID biçiminde X-Request-Id eklenir', function (): void {
    $id = $this->get('/')->headers->get('X-Request-Id');

    expect($id)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
});

it('NFR-OBS-001: geçerli biçimde gelen istek kimliği taşınır', function (): void {
    $this->get('/', ['X-Request-Id' => 'proxy-req-12345678'])
        ->assertHeader('X-Request-Id', 'proxy-req-12345678');
});

it('NFR-OBS-001: geçersiz istek kimliği reddedilip yenisi üretilir (log enjeksiyonu önlenir)', function (): void {
    $id = $this->get('/', ['X-Request-Id' => "kötü\nkimlik<script>"])->headers->get('X-Request-Id');

    expect($id)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
});
