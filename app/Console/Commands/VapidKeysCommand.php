<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Web Push VAPID anahtar çifti üretir — docs/OPERATIONS.md. Anahtarlar yalnızca ekrana yazılır; `.env`'e operatör
 * ekler (`VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`). Anahtar değişirse mevcut abonelikler geçersizleşir.
 */
#[Signature('mrp:vapid-keys')]
#[Description('Web Push için VAPID anahtar çifti üretir (.env değerleri)')]
final class VapidKeysCommand extends Command
{
    public function handle(): int
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (\Throwable $e) {
            // Windows/XAMPP: OpenSSL EC anahtar üretimi için OPENSSL_CONF (openssl.cnf yolu) gerekebilir.
            $this->error('VAPID anahtarı üretilemedi: '.$e->getMessage().'. OpenSSL yapılandırmasını (OPENSSL_CONF) kontrol edin veya komutu Linux sunucuda çalıştırın.');

            return self::FAILURE;
        }

        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->warn('Özel anahtarı yalnızca sunucu .env dosyasına yazın; depoya eklemeyin.');

        return self::SUCCESS;
    }
}
