<?php

declare(strict_types=1);

use App\Enums\ServerStatus;
use App\Models\MtaServer;
use App\Services\Bridge\ServerStatusResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function serverWithHeartbeat(?int $secondsAgo, bool $maintenance = false): MtaServer
{
    return new MtaServer([
        'slug' => 'main', 'name' => 'Test', 'connect_host' => '127.0.0.1', 'connect_port' => 22003,
        'last_player_count' => 42, 'max_players' => 100, 'is_maintenance' => $maintenance,
        'last_heartbeat_at' => $secondsAgo === null ? null : CarbonImmutable::parse('2026-11-11 12:00:00')->subSeconds($secondsAgo),
    ]);
}

it('FR-BRG-010: heartbeat yaşına göre durum sınırları doğru', function (?int $age, ServerStatus $expected): void {
    $snapshot = app(ServerStatusResolver::class)->resolve(serverWithHeartbeat($age), CarbonImmutable::parse('2026-11-11 12:00:00'));

    expect($snapshot->status)->toBe($expected);
})->with([
    'hiç heartbeat yok' => [null, ServerStatus::Offline],
    '0 sn' => [0, ServerStatus::Online],
    '89 sn' => [89, ServerStatus::Online],
    '90 sn' => [90, ServerStatus::Stale],
    '300 sn' => [300, ServerStatus::Stale],
    '301 sn' => [301, ServerStatus::Offline],
]);

it('FR-BRG-010: bakım bayrağı diğer tüm durumlara baskındır', function (): void {
    $snapshot = app(ServerStatusResolver::class)->resolve(serverWithHeartbeat(5, maintenance: true), CarbonImmutable::parse('2026-11-11 12:00:00'));

    expect($snapshot->status)->toBe(ServerStatus::Maintenance);
});

it('US-PUB-03 KK3: çevrimdışı durumda oyuncu sayısı gösterilmez', function (): void {
    $snapshot = app(ServerStatusResolver::class)->resolve(serverWithHeartbeat(600), CarbonImmutable::parse('2026-11-11 12:00:00'));

    expect($snapshot->toArray()['players'])->toBeNull()
        ->and($snapshot->toArray()['connect']['uri'])->toBe('mtasa://127.0.0.1:22003');
});

it('US-PUB-03 KK2: bayat durumda son bilinen sayı gösterilir', function (): void {
    $snapshot = app(ServerStatusResolver::class)->resolve(serverWithHeartbeat(120), CarbonImmutable::parse('2026-11-11 12:00:00'));

    expect($snapshot->toArray()['players'])->toBe(42);
});
