<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Economy\ReconcileWallets;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Günlük 02:00 — docs/ARCHITECTURE.md §10 `wallet:reconcile`, SRS FR-WAL-007. Fark varsa çıkış kodu 1. */
#[Signature('wallet:reconcile')]
#[Description('Cüzdan bakiyelerini ledger toplamlarıyla karşılaştırır')]
final class ReconcileWalletsCommand extends Command
{
    public function handle(ReconcileWallets $reconcile): int
    {
        $run = $reconcile->run();
        $this->info("Kontrol edilen: {$run->wallets_checked}, fark: {$run->mismatches}");

        return $run->mismatches === 0 ? self::SUCCESS : self::FAILURE;
    }
}
