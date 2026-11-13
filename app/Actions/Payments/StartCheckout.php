<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Models\PaymentOrder;
use App\Models\PaymentPackage;
use App\Models\User;
use App\Services\Payments\PaymentManager;
use App\Services\Wallet\WalletService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Checkout — docs/SRS.md FR-PAY-002. Tutar, para birimi ve kredi **her zaman** sunucudaki paket kaydından alınır;
 * istemciden gelen tutar yok sayılır. Aynı `idempotency_key` (form) ikinci kez gelirse mevcut sipariş döner.
 */
final class StartCheckout
{
    public const ORDER_TTL_MINUTES = 30;

    public function __construct(private readonly PaymentManager $providers, private readonly WalletService $wallets) {}

    /** @return array{order: PaymentOrder, redirect: string|null} */
    public function handle(User $user, int $packageId, string $idempotencyKey): array
    {
        $existing = PaymentOrder::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            abort_unless($existing->user_id === $user->id, 404);

            return ['order' => $existing, 'redirect' => null];
        }

        $package = PaymentPackage::query()->with('walletType')->whereKey($packageId)->where('is_active', true)->first();
        if ($package === null || ! $package->walletType->is_purchasable || ! $package->walletType->is_active) {
            throw ValidationException::withMessages(['package_id' => __('economy.errors.package')]);
        }

        $provider = $this->providers->default();
        $wallet = $this->wallets->for($user, $package->walletType->key);

        try {
            $order = DB::transaction(fn (): PaymentOrder => PaymentOrder::query()->create([
                'order_number' => $this->orderNumber(),
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'payment_package_id' => $package->id,
                'provider' => $provider->key(),
                'amount_minor' => $package->price_minor,
                'currency' => $package->currency,
                'credit_amount' => $package->totalCredit(),
                'status' => 'created',
                'idempotency_key' => $idempotencyKey,
                'expires_at' => now()->addMinutes(self::ORDER_TTL_MINUTES),
            ]));
        } catch (UniqueConstraintViolationException) {
            $order = PaymentOrder::query()->where('idempotency_key', $idempotencyKey)->where('user_id', $user->id)->firstOrFail();

            return ['order' => $order, 'redirect' => null];
        }

        $checkout = $provider->createCheckout($order);
        $order->forceFill(['provider_order_id' => $checkout->providerOrderId, 'status' => 'pending'])->save();

        return ['order' => $order, 'redirect' => $checkout->redirectUrl];
    }

    /** `MRP-YYYYMMDD-XXXX` (Crockford Base32; çakışmada yeniden üretilir). */
    private function orderNumber(): string
    {
        $alphabet = (string) config('roleplay.link_code.alphabet');

        do {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $number = 'MRP-'.now()->format('Ymd').'-'.$suffix;
        } while (PaymentOrder::query()->where('order_number', $number)->exists());

        return $number;
    }
}
