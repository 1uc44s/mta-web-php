<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletType;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Cüzdan erişimi — docs/SRS.md FR-WAL-001: kullanıcı başına tür başına 1 cüzdan, ilk ihtiyaçta oluşturulur
 * (`uq_wallets_user_type` yarışı güvenli kılar). Bakiye değişikliği burada değil, yalnızca `LedgerService`'te.
 */
final class WalletService
{
    public function for(User $user, string $typeKey = WalletType::WEB_CREDIT): Wallet
    {
        $type = WalletType::query()->where('key', $typeKey)->firstOrFail();

        $wallet = Wallet::query()->where('user_id', $user->id)->where('wallet_type_id', $type->id)->first();
        if ($wallet !== null) {
            return $wallet;
        }

        try {
            return Wallet::query()->create(['user_id' => $user->id, 'wallet_type_id' => $type->id, 'status' => 'active']);
        } catch (UniqueConstraintViolationException) {
            return Wallet::query()->where('user_id', $user->id)->where('wallet_type_id', $type->id)->firstOrFail();
        }
    }
}
