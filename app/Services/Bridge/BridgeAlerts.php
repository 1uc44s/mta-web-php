<?php

declare(strict_types=1);

namespace App\Services\Bridge;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLogger;
use App\Services\Integrations\OpsAlerts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Köprü doğrulama hataları — docs/SRS.md FR-BRG-008. Her hata `security` kanalına (secret/imza içermeden) yazılır;
 * 5 dk'lık pencerede 20'yi aşarsa bir kez kritik audit kaydı (`bridge.signature_alert`) oluşturulur ve ACP sağlık
 * ekranında görünür.
 */
final class BridgeAlerts
{
    public const THRESHOLD = 20;

    public const WINDOW_SECONDS = 300;

    public function __construct(private readonly AuditLogger $audit, private readonly OpsAlerts $ops) {}

    /** @param  array<string, scalar|null>  $context */
    public function failure(string $reason, array $context = []): void
    {
        Log::channel('security')->warning('bridge.'.$reason, $context);

        $bucket = 'bridge:failures:'.intdiv(time(), self::WINDOW_SECONDS);
        Cache::add($bucket, 0, self::WINDOW_SECONDS * 2);
        $count = (int) Cache::increment($bucket);

        if ($count === self::THRESHOLD + 1 && Cache::add($bucket.':alerted', true, self::WINDOW_SECONDS * 2)) {
            $this->audit->record(AuditAction::BridgeSignatureAlert, null, ['after' => ['failures' => $count, 'window_seconds' => self::WINDOW_SECONDS, 'last_reason' => $reason]], null, null, 'bridge');
            $this->ops->critical('bridge.signature_alert', ['failures' => $count, 'last_reason' => $reason]);
        }
    }
}
