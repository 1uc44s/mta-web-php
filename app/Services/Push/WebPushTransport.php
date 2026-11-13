<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * VAPID imzalı Web Push gönderimi (RFC 8030/8291/8292). Anahtarlar `.env` (`VAPID_*`, `php artisan mrp:vapid-keys`).
 */
final class WebPushTransport implements PushTransport
{
    private ?WebPush $client = null;

    public function deliver(PushSubscription $subscription, string $payload): int
    {
        $report = $this->client()->sendOneNotification(Subscription::create([
            'endpoint' => $subscription->endpoint,
            'publicKey' => $subscription->public_key,
            'authToken' => $subscription->auth_token,
            'contentEncoding' => $subscription->content_encoding,
        ]), $payload, ['TTL' => 3600, 'urgency' => 'normal']);

        if ($report->isSuccess()) {
            return 201;
        }

        return $report->getResponse()?->getStatusCode() ?? 0;
    }

    private function client(): WebPush
    {
        return $this->client ??= new WebPush([
            'VAPID' => [
                'subject' => (string) config('roleplay.push.subject'),
                'publicKey' => (string) config('roleplay.push.public_key'),
                'privateKey' => (string) config('roleplay.push.private_key'),
            ],
        ]);
    }
}
