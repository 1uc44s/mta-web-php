<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\LedgerService;
use App\Services\Wallet\WalletService;
use Database\Seeders\EconomySeeder;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;

/*
 * Faz 9 çıkış kriteri — "Eşzamanlı 50 debit isteğinde bakiye negatife düşmez". Gerçek eşzamanlılık için 50 ayrı PHP
 * süreci aynı cüzdana paralel debit dener (tests/Support/ledger_debit_worker.php). Süreçler test transaction'ını
 * göremeyeceği için bu dosya RefreshDatabase KULLANMAZ: veri commit edilir ve sonda temizlenir.
 */

beforeEach(function (): void {
    $this->artisan('migrate', ['--force' => true]);
});

afterEach(function (): void {
    // wallet_transactions append-only (DELETE trigger): TRUNCATE trigger çalıştırmaz. Yalnızca bu testin verisi.
    Schema::withoutForeignKeyConstraints(function (): void {
        DB::statement('TRUNCATE TABLE wallet_transactions');
        DB::table('wallets')->delete();
        DB::table('payment_packages')->delete();
        DB::table('wallet_types')->delete();
        DB::table('users')->where('email', 'like', 'concurrency+%@example.test')->delete();
    });
});

it('Çıkış kriteri: eşzamanlı 50 debit isteğinde bakiye negatife düşmez ve ledger tutarlı kalır', function (): void {
    (new EconomySeeder)->run();
    $user = User::factory()->create(['email' => 'concurrency+'.uniqid().'@example.test']);
    $wallet = app(WalletService::class)->for($user);
    app(LedgerService::class)->post($wallet->id, 'credit', 1000, 'promo', 'seed:'.$wallet->id, 'Başlangıç');

    $php = PHP_BINARY;
    $worker = base_path('tests/Support/ledger_debit_worker.php');
    $env = ['APP_ENV' => 'testing', 'DB_DATABASE' => (string) config('database.connections.mariadb.database'), 'CACHE_STORE' => 'array', 'LOG_CHANNEL' => 'null'];

    $results = Process::pool(function (Pool $pool) use ($php, $worker, $wallet, $env): void {
        for ($i = 0; $i < 50; $i++) {
            $pool->path(base_path())->env($env)->timeout(120)->command([$php, $worker, (string) $wallet->id, '30', 'concurrent:'.$wallet->id.':'.$i]);
        }
    })->start()->wait();

    $outputs = collect($results)->map(fn ($r) => trim($r->output()));

    expect($outputs->filter(fn ($o) => ! in_array($o, ['ok', 'insufficient'], true))->values()->all())->toBe([])
        ->and($outputs->filter(fn ($o) => $o === 'ok')->count())->toBe(33) // 1000 / 30 → 33 başarılı
        ->and($outputs->filter(fn ($o) => $o === 'insufficient')->count())->toBe(17);

    $fresh = Wallet::query()->findOrFail($wallet->id);
    $net = (int) DB::table('wallet_transactions')->where('wallet_id', $wallet->id)
        ->selectRaw("SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) AS net")->value('net');

    expect($fresh->balance)->toBe(10)
        ->and($net)->toBe($fresh->balance)
        ->and($fresh->version)->toBe(34)
        ->and((int) DB::table('wallet_transactions')->where('wallet_id', $wallet->id)->min('balance_after'))->toBeGreaterThanOrEqual(0);
});
