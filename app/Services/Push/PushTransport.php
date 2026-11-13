<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Models\PushSubscription;

/**
 * Web Push iletim katmanı — docs/ARCHITECTURE.md §8.1. Gerçek uygulama `WebPushTransport` (VAPID, minishlink/web-push);
 * testler sahte bir uygulama bağlar. Dönen değer push servisinin HTTP durum kodudur (ağ hatası: 0).
 */
interface PushTransport
{
    public function deliver(PushSubscription $subscription, string $payload): int;
}
