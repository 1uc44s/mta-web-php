<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Jobs\SendDiscordMessage;
use Illuminate\Support\Facades\Cache;

/**
 * Kritik operasyon uyarıları Discord'a — docs/OPERATIONS.md. `discord.ops_alerts` kapalıysa veya webhook yoksa
 * sessizce atlanır (log/audit zaten yazılmıştır). Aynı kontrol 30 dk içinde bir kez gönderilir.
 */
final class OpsAlerts
{
    public const DEDUPE_SECONDS = 1800;

    public function __construct(private readonly DiscordWebhook $discord) {}

    /** @param  array<string, scalar|null>  $context */
    public function critical(string $check, array $context = []): void
    {
        if (! $this->discord->enabled('ops') || ! Cache::add('ops-alert:'.$check, true, self::DEDUPE_SECONDS)) {
            return;
        }

        $details = collect($context)->map(fn ($v, string $k): string => $k.'='.(is_bool($v) ? ($v ? 'true' : 'false') : (string) $v))->implode(', ');
        $line = '[KRİTİK] '.config('app.name').' · '.$check.($details === '' ? '' : ' · '.$details);

        SendDiscordMessage::dispatch('ops', DiscordWebhook::sanitize($line), 'ops.'.$check);
    }
}
