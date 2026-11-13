<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MtaServer;
use App\Services\Integrations\OpsAlerts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Operasyonel alarmlar — docs/ROADMAP.md 11.6, docs/OPERATIONS.md §5. 5 dk'da bir çalışır; eşik aşımlarını `security`
 * kanalına `critical`/`warning` olarak yazar (log toplayıcı / e-posta alarmı bu satırları izler). Çıkış kodu 1 = alarm var
 * (harici uptime izleyicisi `php artisan mrp:health-check` sonucunu da kullanabilir).
 */
#[Signature('mrp:health-check {--json : Sonucu JSON olarak yaz}')]
#[Description('Başarısız işler, kuyruk gecikmesi, disk ve MTA heartbeat eşiklerini denetler')]
final class HealthCheckCommand extends Command
{
    public const FAILED_JOBS_WARN = 1;

    public const QUEUE_AGE_WARN_SECONDS = 600;

    public const DISK_FREE_WARN_RATIO = 0.10;

    public const HEARTBEAT_WARN_SECONDS = 300;

    public function handle(OpsAlerts $ops): int
    {
        $alerts = [];

        $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
        if ($failed >= self::FAILED_JOBS_WARN) {
            $alerts[] = ['level' => 'warning', 'check' => 'failed_jobs', 'value' => $failed];
        }

        $oldest = DB::table('jobs')->min('created_at');
        $age = is_numeric($oldest) ? time() - (int) $oldest : 0;
        if ($age > self::QUEUE_AGE_WARN_SECONDS) {
            $alerts[] = ['level' => 'critical', 'check' => 'queue_age_seconds', 'value' => $age];
        }

        $total = @disk_total_space(storage_path());
        $free = @disk_free_space(storage_path());
        if (is_float($total) && is_float($free) && $total > 0 && $free / $total < self::DISK_FREE_WARN_RATIO) {
            $alerts[] = ['level' => 'critical', 'check' => 'disk_free_ratio', 'value' => round($free / $total, 3)];
        }

        foreach (MtaServer::query()->where('is_active', true)->where('is_maintenance', false)->get() as $server) {
            $silent = $server->last_heartbeat_at === null ? null : (int) $server->last_heartbeat_at->diffInSeconds(now(), absolute: true);
            if ($silent === null || $silent > self::HEARTBEAT_WARN_SECONDS) {
                $alerts[] = ['level' => 'warning', 'check' => 'heartbeat:'.$server->slug, 'value' => $silent];
            }
        }

        foreach ($alerts as $alert) {
            Log::channel('security')->log($alert['level'], 'health.'.$alert['check'], ['value' => $alert['value']]);
            if ($alert['level'] === 'critical') {
                $ops->critical('health.'.$alert['check'], ['value' => $alert['value']]);
            }
        }

        if ($this->option('json')) {
            $this->line((string) json_encode(['ok' => $alerts === [], 'alerts' => $alerts], JSON_UNESCAPED_SLASHES));
        } else {
            $this->info($alerts === [] ? 'Tüm kontroller normal.' : count($alerts).' alarm: '.implode(', ', array_column($alerts, 'check')));
        }

        return $alerts === [] ? self::SUCCESS : self::FAILURE;
    }
}
