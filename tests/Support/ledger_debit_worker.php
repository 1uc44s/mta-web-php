<?php

declare(strict_types=1);

/*
 * Eşzamanlılık testi işçisi — docs/ROADMAP.md Faz 9 çıkış kriteri ("eşzamanlı 50 debit isteğinde bakiye negatife
 * düşmez"). tests/Feature/Payments/LedgerConcurrencyTest.php bunu ayrı PHP süreçleri olarak paralel çalıştırır;
 * her süreç kendi DB bağlantısıyla `LedgerService::post()` çağırır. Çıktı: `ok` | `insufficient` | `error:<mesaj>`.
 *
 * Kullanım: php tests/Support/ledger_debit_worker.php <wallet_id> <amount> <idempotency_key>
 */

use App\Services\Wallet\LedgerException;
use App\Services\Wallet\LedgerService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $walletId, $amount, $key] = $argv + [null, '0', '0', ''];

try {
    $app->make(LedgerService::class)->post((int) $walletId, 'debit', (int) $amount, 'purchase', (string) $key, 'Eşzamanlılık testi');
    echo 'ok';
} catch (LedgerException $e) {
    echo $e->errorCode === 'INSUFFICIENT_BALANCE' ? 'insufficient' : 'error:'.$e->errorCode;
} catch (Throwable $e) {
    echo 'error:'.$e::class;
}
