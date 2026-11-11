<?php

declare(strict_types=1);

use App\Support\IpMasker;
use App\Support\Url;

it('SEC-026: yalnızca aynı origin göreli yollara yönlendirilir', function (mixed $target, string $expected): void {
    expect(Url::safeRedirect($target, '/fallback'))->toBe($expected);
})->with([
    ['/rules', '/rules'],
    ['/news?page=2', '/news?page=2'],
    ['//evil.example', '/fallback'],
    ['https://evil.example', '/fallback'],
    ['/\\evil.example', '/fallback'],
    ["/rules\n", '/fallback'],
    [null, '/fallback'],
    [['/x'], '/fallback'],
]);

it('RULES-6: kullanıcı URL şeması allowlist ile süzülür', function (string $url, string $expected): void {
    expect(Url::safe($url))->toBe($expected);
})->with([
    ['https://discord.gg/abc', 'https://discord.gg/abc'],
    ['javascript:alert(1)', '#'],
    ['data:text/html,x', '#'],
    ['mailto:a@b.co', 'mailto:a@b.co'],
]);

it('SEC-043: IP adresleri maskelenir ve hash ham IP içermez', function (): void {
    expect(IpMasker::mask('85.105.12.34'))->toBe('85.105.x.x')
        ->and(IpMasker::mask('2a02:e0:12:34::1'))->toBe('2a02:e0:12::/48')
        ->and(IpMasker::mask('geçersiz'))->toBeNull()
        ->and(IpMasker::hash('85.105.12.34'))->toHaveLength(64)->not->toContain('85.105');
});
