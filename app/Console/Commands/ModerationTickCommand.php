<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Bridge\GameCommandQueue;
use App\Actions\Moderation\ManageSanctions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Her dakika — docs/ARCHITECTURE.md §10: sonuçsuz oyun komutlarını yeniden kuyruğa alır (FR-BRG-011) ve süresi dolan
 * cezaları `expired` yapar; web askısı biten kullanıcıyı yeniden aktifleştirir (FR-MOD-006).
 */
#[Signature('mrp:moderation-tick')]
#[Description('Oyun komutlarını yeniden kuyruğa alır ve süresi dolan cezaları kapatır')]
final class ModerationTickCommand extends Command
{
    public function handle(GameCommandQueue $queue, ManageSanctions $sanctions): int
    {
        $requeued = $queue->requeueStale();
        $expired = $sanctions->expireDue();

        $this->info("Komut: {$requeued}, süresi dolan ceza: {$expired}");

        return self::SUCCESS;
    }
}
