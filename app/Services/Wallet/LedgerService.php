<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Tek yazım noktası — docs/SRS.md FR-WAL-002..005, DATABASE §6.2–6.3.
 *   DB transaction → `SELECT … FOR UPDATE` (cüzdan satırı) → idempotency kontrolü → ledger INSERT → bakiye + version++.
 * Başka hiçbir kod `wallets.balance` alanına yazmaz (mimari testi: tests/Arch/ArchitectureTest.php). Aynı
 * `idempotency_key` mevcut kaydı döndürür; debit bakiyeyi negatife düşüremez (servis + DB CHECK).
 */
final class LedgerService
{
    /**
     * `$direction`: `credit|debit` (çalışma zamanında doğrulanır; çağıranlar DB'den okunan değerleri geçirebilir).
     *
     * @throws LedgerException
     */
    public function post(
        int $walletId,
        string $direction,
        int $amount,
        string $reason,
        string $idempotencyKey,
        string $description,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?User $createdBy = null,
        ?int $reversalOfId = null,
    ): WalletTransaction {
        if ($amount <= 0 || ! in_array($direction, ['credit', 'debit'], true) || ! in_array($reason, WalletTransaction::REASONS, true)) {
            throw new InvalidArgumentException('Geçersiz ledger kaydı.');
        }

        return DB::transaction(function () use ($walletId, $direction, $amount, $reason, $idempotencyKey, $description, $sourceType, $sourceId, $createdBy, $reversalOfId): WalletTransaction {
            $wallet = DB::table('wallets')->where('id', $walletId)->lockForUpdate()->first(['id', 'balance', 'status']);

            if ($wallet === null) {
                throw new LedgerException('WALLET_NOT_FOUND', 'Cüzdan bulunamadı.');
            }

            // Kilit alındıktan sonra kontrol: aynı anahtarla eşzamanlı ikinci istek ilk kaydı görür.
            $existing = WalletTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                if ($existing->wallet_id !== $walletId || $existing->amount !== $amount || $existing->direction !== $direction) {
                    throw new LedgerException('IDEMPOTENCY_CONFLICT', 'Aynı anahtar farklı bir işlem için kullanılmış.');
                }

                return $existing;
            }

            if ($direction === 'debit' && $wallet->status === 'frozen') {
                throw new LedgerException('WALLET_FROZEN', 'Cüzdan dondurulmuş.');
            }

            $before = (int) $wallet->balance;
            $after = $direction === 'credit' ? $before + $amount : $before - $amount;

            if ($after < 0) {
                throw new LedgerException('INSUFFICIENT_BALANCE', 'Yetersiz bakiye.');
            }

            $transaction = WalletTransaction::query()->create([
                'ulid' => strtolower((string) Str::ulid()),
                'wallet_id' => $walletId,
                'direction' => $direction,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'reason' => $reason,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'reversal_of_id' => $reversalOfId,
                'idempotency_key' => $idempotencyKey,
                'description' => mb_substr($description, 0, 255),
                'created_by' => $createdBy?->id,
                'created_at' => now(),
            ]);

            DB::table('wallets')->where('id', $walletId)->update([
                'balance' => $after,
                'version' => DB::raw('version + 1'),
                'updated_at' => now(),
            ]);

            return $transaction;
        }, attempts: 3);
    }

    /**
     * FR-WAL-003: düzeltme ters kayıtla yapılır; `uq_wt_reversal_of` bir kaydın yalnızca bir kez ters çevrilmesini sağlar.
     *
     * @throws LedgerException
     */
    public function reverse(WalletTransaction $original, string $description, ?User $createdBy, ?string $sourceType = null, ?int $sourceId = null): WalletTransaction
    {
        if ($original->reason === 'reversal') {
            throw new LedgerException('NOT_REVERSIBLE', 'Ters kayıt yeniden ters çevrilemez.');
        }

        if (WalletTransaction::query()->where('reversal_of_id', $original->id)->exists()) {
            throw new LedgerException('ALREADY_REVERSED', 'Bu kayıt zaten ters çevrilmiş.');
        }

        return $this->post(
            $original->wallet_id,
            $original->direction === 'credit' ? 'debit' : 'credit',
            $original->amount,
            'reversal',
            'reversal:'.$original->ulid,
            $description,
            $sourceType,
            $sourceId,
            $createdBy,
            $original->id,
        );
    }
}
