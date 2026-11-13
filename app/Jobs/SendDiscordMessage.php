<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MessageDispatch;
use App\Services\Integrations\DiscordWebhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Discord webhook gönderimi — docs/SRS.md FR-NTF-002. Her deneme `message_dispatches` (channel `discord_webhook`)
 * satırı yazar. İçerik çağıran tarafından `DiscordWebhook::sanitize` ile hazırlanmış olmalıdır.
 */
final class SendDiscordMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(public readonly string $target, public readonly string $content, public readonly string $key) {}

    public function handle(DiscordWebhook $discord): void
    {
        $dispatch = MessageDispatch::query()->create([
            'template_key' => $this->key,
            'channel' => 'discord_webhook',
            'recipient_masked' => 'discord:'.$this->target,
            'status' => 'queued',
            'attempts' => $this->attempts(),
            'queued_at' => now(),
        ]);

        $url = $discord->url($this->target);
        if ($url === null) {
            $dispatch->forceFill(['status' => 'failed', 'error' => 'webhook_not_configured'])->save();

            return;
        }

        try {
            $response = Http::timeout(5)->acceptJson()->post($url, DiscordWebhook::payload($this->content));
        } catch (Throwable $e) {
            $dispatch->forceFill(['status' => 'failed', 'error' => class_basename($e)])->save();

            throw $e;
        }

        if ($response->successful()) {
            $dispatch->forceFill(['status' => 'sent', 'sent_at' => now()])->save();

            return;
        }

        $dispatch->forceFill(['status' => 'failed', 'error' => 'http_'.$response->status()])->save();

        if ($response->status() === 429 || $response->serverError()) {
            $this->release(30);
        }
    }
}
