<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Account\AccountDeletion;
use App\Actions\Support\TicketWorkflow;
use App\Services\Settings\SettingsRepository;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Zamanlanmış hesap ve destek bakımı — docs/SRS.md FR-SUP-005 (otomatik çözüldü/kapandı) ve FR-AUTH-020 (14 gün sonra
 * anonimleştirme). Saatlik çalışır; idempotenttir.
 */
#[Signature('mrp:maintenance-tick')]
#[Description('Destek otomatik geçişleri ve süresi dolan hesap silme taleplerini işler')]
final class SupportAutoTransition extends Command
{
    public function handle(TicketWorkflow $tickets, AccountDeletion $deletion, SettingsRepository $settings): int
    {
        $moved = $tickets->autoTransition((int) $settings->get('support.auto_resolve_days', 7), (int) $settings->get('support.auto_close_days', 7));
        $anonymized = $deletion->processDue();

        $this->info("Ticket geçişi: {$moved} · anonimleştirilen hesap: {$anonymized}");

        return self::SUCCESS;
    }
}
