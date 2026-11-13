<?php

declare(strict_types=1);

namespace App\Services\Payments;

use InvalidArgumentException;

/**
 * Sağlayıcı çözümleyici — `config/roleplay.php payments.provider` (`PAYMENTS_PROVIDER`). OD-04 kararına kadar yalnızca
 * `fake` kayıtlıdır ve production ortamında reddedilir; gerçek adapter bu eşlemeye eklenir.
 */
final class PaymentManager
{
    /** @var array<string, class-string<PaymentProvider>> */
    private const PROVIDERS = [
        'fake' => FakeProvider::class,
    ];

    public function default(): PaymentProvider
    {
        return $this->get((string) config('roleplay.payments.provider', 'fake'));
    }

    public function get(string $key): PaymentProvider
    {
        if (! $this->exists($key)) {
            throw new InvalidArgumentException("Ödeme sağlayıcısı kullanılamaz: {$key}");
        }

        return app(self::PROVIDERS[$key]);
    }

    public function exists(string $key): bool
    {
        $testOnly = in_array($key, (array) config('roleplay.payments.test_providers', []), true);

        return isset(self::PROVIDERS[$key]) && ! ($testOnly && app()->isProduction());
    }
}
