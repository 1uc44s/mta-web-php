<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Jobs\SendWebPush;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Notifications\NotificationPreferences;
use App\Services\Push\PushSubscriptions;
use Illuminate\Notifications\Notification;

/**
 * `webpush` bildirim kanalı — docs/ARCHITECTURE.md §8.1, DATABASE §3.11. Bildirim `toWebPush()` ile
 * `{category, title, body, url, tag}` döner; kategori tercihi (`push`) kapalıysa veya abonelik yoksa gönderilmez.
 */
final class WebPushChannel
{
    public function __construct(private readonly PushSubscriptions $push, private readonly NotificationPreferences $preferences) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User || ! method_exists($notification, 'toWebPush') || ! $this->push->available()) {
            return;
        }

        /** @var array{category: string|null, title: string, body: string|null, url: string, tag: string}|null $message */
        $message = $notification->toWebPush($notifiable);
        if ($message === null || $message['category'] === null) {
            return;
        }

        if (! $this->preferences->allows($notifiable, $message['category'], 'push')
            || ! PushSubscription::query()->where('user_id', $notifiable->id)->exists()) {
            return;
        }

        SendWebPush::dispatch($notifiable->id, ['title' => $message['title'], 'body' => $message['body'], 'url' => $message['url'], 'tag' => $message['tag']]);
    }
}
