<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Wallet\LedgerException;
use App\Services\Wallet\LedgerService;
use App\Services\Wallet\WalletService;
use Database\Seeders\EconomySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new EconomySeeder)->run();
});

function ledgerWallet(): Wallet
{
    return app(WalletService::class)->for(User::factory()->create());
}

it('FR-WAL-001: kullanıcı başına tür başına tek cüzdan (lazy create)', function (): void {
    $user = User::factory()->create();
    $a = app(WalletService::class)->for($user);
    $b = app(WalletService::class)->for($user);

    expect($a->id)->toBe($b->id)->and(Wallet::query()->count())->toBe(1);
});

it('FR-WAL-002: post bakiyeyi ve version alanını günceller, önce/sonra bakiyeyi yazar', function (): void {
    $wallet = ledgerWallet();
    $ledger = app(LedgerService::class);

    $t1 = $ledger->post($wallet->id, 'credit', 500, 'promo', 'k1', 'Hoş geldin');
    $t2 = $ledger->post($wallet->id, 'debit', 120, 'purchase', 'k2', 'Satın alma');

    expect($t1->balance_before)->toBe(0)->and($t1->balance_after)->toBe(500)
        ->and($t2->balance_before)->toBe(500)->and($t2->balance_after)->toBe(380)
        ->and($wallet->fresh()->balance)->toBe(380)->and($wallet->fresh()->version)->toBe(2);
});

it('FR-WAL-004: aynı idempotency anahtarı mevcut kaydı döner; farklı içerikle çakışma hatası', function (): void {
    $wallet = ledgerWallet();
    $ledger = app(LedgerService::class);

    $first = $ledger->post($wallet->id, 'credit', 100, 'topup', 'pay:x', 'Yükleme');
    $again = $ledger->post($wallet->id, 'credit', 100, 'topup', 'pay:x', 'Yükleme');

    expect($again->id)->toBe($first->id)->and($wallet->fresh()->balance)->toBe(100)
        ->and(fn () => $ledger->post($wallet->id, 'credit', 999, 'topup', 'pay:x', 'Yükleme'))->toThrow(LedgerException::class);
});

it('FR-WAL-005: debit bakiyeyi negatife düşüremez (servis + DB CHECK)', function (): void {
    $wallet = ledgerWallet();
    $ledger = app(LedgerService::class);
    $ledger->post($wallet->id, 'credit', 50, 'promo', 'c', 'x');

    expect(fn () => $ledger->post($wallet->id, 'debit', 51, 'purchase', 'd', 'x'))->toThrow(LedgerException::class)
        ->and($wallet->fresh()->balance)->toBe(50)
        ->and(fn () => DB::table('wallets')->where('id', $wallet->id)->update(['balance' => -1]))->toThrow(QueryException::class);
});

it('FR-WAL-003: ledger append-only; düzeltme yalnızca bir kez ters kayıtla', function (): void {
    $wallet = ledgerWallet();
    $ledger = app(LedgerService::class);
    $original = $ledger->post($wallet->id, 'credit', 300, 'promo', 'p', 'Promosyon');

    expect(fn () => DB::table('wallet_transactions')->where('id', $original->id)->update(['amount' => 1]))->toThrow(QueryException::class)
        ->and(fn () => DB::table('wallet_transactions')->where('id', $original->id)->delete())->toThrow(QueryException::class);

    $reversal = $ledger->reverse($original, 'Hatalı promosyon', null);
    expect($reversal->direction)->toBe('debit')->and($reversal->reversal_of_id)->toBe($original->id)
        ->and($wallet->fresh()->balance)->toBe(0)
        ->and(fn () => $ledger->reverse($original, 'Tekrar', null))->toThrow(LedgerException::class)
        ->and(fn () => $ledger->reverse($reversal, 'Ters kaydın tersi', null))->toThrow(LedgerException::class);
});

it('FR-WAL-002: dondurulmuş cüzdandan debit yapılamaz, credit yapılabilir', function (): void {
    $wallet = ledgerWallet();
    $ledger = app(LedgerService::class);
    $ledger->post($wallet->id, 'credit', 100, 'promo', 'a', 'x');
    $wallet->forceFill(['status' => 'frozen'])->save();

    expect(fn () => $ledger->post($wallet->id, 'debit', 10, 'purchase', 'b', 'x'))->toThrow(LedgerException::class);
    $ledger->post($wallet->id, 'credit', 10, 'promo', 'c', 'x');
    expect($wallet->fresh()->balance)->toBe(110)->and(WalletTransaction::query()->count())->toBe(2);
});
