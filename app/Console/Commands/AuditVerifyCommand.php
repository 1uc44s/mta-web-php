<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Audit\AuditLogger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** docs/DATABASE.md §11.3: audit hash zincirini baştan doğrular. Bozulma varsa çıkış kodu 1. */
#[Signature('audit:verify')]
#[Description('Audit log hash zincirini doğrular')]
final class AuditVerifyCommand extends Command
{
    public function handle(AuditLogger $audit): int
    {
        $count = DB::table('audit_logs')->count();
        $broken = $audit->verify();

        if ($broken !== null) {
            $this->error("Zincir #{$broken} numaralı kayıtta bozuk.");

            return self::FAILURE;
        }

        $this->info("Zincir sağlam ({$count} kayıt).");

        return self::SUCCESS;
    }
}
