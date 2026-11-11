<?php

declare(strict_types=1);

use App\Logging\RedactSensitiveData;
use Illuminate\Support\Facades\Log;

it('SEC-043: hassas anahtarları iç içe dizilerde de maskeler, diğerlerine dokunmaz', function (): void {
    $out = RedactSensitiveData::redact([
        'password' => 'p', 'password_confirmation' => 'p', 'remember_token' => 't', 'X-Bridge-Signature' => 's',
        'code' => '123456', 'status_code' => 500, 'username' => 'kaan',
        'headers' => ['authorization' => 'Bearer x', 'cookie' => 'c', 'accept' => 'json'],
    ]);

    expect($out)->toMatchArray([
        'password' => '[REDACTED]', 'password_confirmation' => '[REDACTED]', 'remember_token' => '[REDACTED]',
        'X-Bridge-Signature' => '[REDACTED]', 'code' => '[REDACTED]', 'status_code' => 500, 'username' => 'kaan',
    ])->and($out['headers'])->toBe(['authorization' => '[REDACTED]', 'cookie' => '[REDACTED]', 'accept' => 'json']);
});

it('SEC-043: tap eklenmiş kanala yazılan logda parola görünmez', function (): void {
    $path = storage_path('logs/redact-test.log');
    @unlink($path);
    config(['logging.channels.redact_test' => ['driver' => 'single', 'path' => $path, 'tap' => [RedactSensitiveData::class]]]);

    Log::channel('redact_test')->info('deneme', ['password' => 'Cok-Gizli-Parola-1', 'user' => 'kaan']);

    $content = (string) file_get_contents($path);
    @unlink($path);
    expect($content)->not->toContain('Cok-Gizli-Parola-1')->toContain('[REDACTED]')->toContain('kaan');
});

it('SEC-043: varsayılan single ve daily kanallarında tap tanımlı', function (): void {
    expect(config('logging.channels.single.tap'))->toContain(RedactSensitiveData::class)
        ->and(config('logging.channels.daily.tap'))->toContain(RedactSensitiveData::class);
});
