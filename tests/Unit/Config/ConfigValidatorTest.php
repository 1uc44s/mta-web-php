<?php

declare(strict_types=1);

use App\Support\ConfigValidator;

it('ROADMAP-1.5: test ortamı yapılandırması geçerlidir', function (): void {
    expect(ConfigValidator::problems())->toBe([]);
});

it('ROADMAP-1.5: eksik veya hatalı değerler anlaşılır şekilde raporlanır', function (): void {
    config([
        'app.key' => '',
        'database.default' => 'sqlite',
        'hashing.driver' => 'bcrypt',
        'roleplay.bridge.serial_pepper' => 'kisa',
    ]);

    expect(ConfigValidator::problems())->toHaveCount(4);
    expect(fn () => ConfigValidator::assertValid())->toThrow(RuntimeException::class, 'APP_KEY');
});

it('SRS-8: production ortamında APP_DEBUG açık bırakılamaz', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config(['app.debug' => true]);

    expect(ConfigValidator::problems())->toContain('Production ortamında APP_DEBUG=false olmalı (docs/SRS.md §8).');
});
