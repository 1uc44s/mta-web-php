<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/* docs/DATABASE.md §7, §11.3 — hash zinciri, append-only, kurcalama tespiti. */

uses(RefreshDatabase::class);

it('DB-11.3: her kayıt bir öncekinin hash’ine bağlanır ve zincir doğrulanır', function (): void {
    $logger = app(AuditLogger::class);
    $a = $logger->record(AuditAction::BranchesCreated, null, ['after' => ['slug' => 'a']], null, null, 'system');
    $b = $logger->record(AuditAction::SettingsUpdated, null, ['key' => 'site.name', 'before' => 'x', 'after' => 'y'], null, null, 'system');

    expect($a->prev_hash)->toBeNull()
        ->and($b->prev_hash)->toBe($a->hash)
        ->and($logger->verify())->toBeNull();

    $this->artisan('audit:verify')->assertSuccessful();
});

it('DB-7: audit_logs UPDATE ve DELETE trigger ile engellenir', function (): void {
    $log = app(AuditLogger::class)->record(AuditAction::BranchesCreated, null, [], null, null, 'system');

    expect(fn () => DB::table('audit_logs')->where('id', $log->id)->update(['reason' => 'değiştirildi']))->toThrow(QueryException::class)
        ->and(fn () => DB::table('audit_logs')->where('id', $log->id)->delete())->toThrow(QueryException::class);
});

it('DB-11.3: zincire sahte kayıt eklenirse doğrulama onu yakalar', function (): void {
    $logger = app(AuditLogger::class);
    $real = $logger->record(AuditAction::BranchesCreated, null, [], null, null, 'system');

    DB::table('audit_logs')->insert([
        'ulid' => strtolower((string) Str::ulid()), 'actor_type' => 'user', 'action' => 'roles.assigned', 'severity' => 'warning',
        'prev_hash' => $real->hash, 'hash' => str_repeat('a', 64), 'created_at' => now(),
    ]);
    $forged = (int) DB::table('audit_logs')->max('id');

    expect($logger->verify())->toBe($forged);
    $this->artisan('audit:verify')->assertFailed();
});

it('ADMIN-8: gerekçe zorunlu eylemlerde gerekçesiz kayıt reddedilir ve hassas alanlar maskelenir', function (): void {
    $logger = app(AuditLogger::class);

    expect(fn () => $logger->record(AuditAction::RolesAssigned, null, [], '  '))->toThrow(InvalidArgumentException::class);

    $log = $logger->record(AuditAction::SettingsUpdated, null, ['after' => ['password' => 'gizli', 'two_factor_secret' => 'x', 'name' => 'ok']], null, null, 'system');
    expect($log->changes['after'])->toBe(['password' => '[REDACTED]', 'two_factor_secret' => '[REDACTED]', 'name' => 'ok'])
        ->and(AuditLog::query()->count())->toBe(1);
});
