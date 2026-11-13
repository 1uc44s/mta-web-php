<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Channels\WebPushChannel;
use App\Support\Realtime;
use Illuminate\Notifications\Notification;

/**
 * Genel panel bildirimi (database kanalı) — docs/SRS.md FR-UCP-002. Faz 8: başvuru kararı / bilgi isteği, ceza,
 * itiraz kararı, cihaz onayı. Yalnızca gösterim alanları; iç notlar kopyalanmaz.
 */
final class PanelNotice extends Notification
{
    public function __construct(
        private readonly string $kind,
        private readonly string $title,
        private readonly string $url,
        private readonly ?string $subject = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return Realtime::withBroadcast(['database', WebPushChannel::class]);
    }

    /** @return array<string, string|null> */
    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'title' => $this->title, 'subject' => $this->subject, 'url' => $this->url];
    }

    /** Push kategorisi (DATABASE §3.11) — eşleşmeyen tür (ör. yetkili mutabakat uyarısı) push almaz. */
    public static function category(string $kind): ?string
    {
        return match (true) {
            str_starts_with($kind, 'application_') => 'applications',
            str_starts_with($kind, 'sanction_'), str_starts_with($kind, 'appeal_') => 'sanctions',
            str_starts_with($kind, 'payment_'), str_starts_with($kind, 'refund_') => 'payments',
            default => null,
        };
    }

    /** @return array{category: string|null, title: string, body: string|null, url: string, tag: string} */
    public function toWebPush(object $notifiable): array
    {
        return ['category' => self::category($this->kind), 'title' => $this->title, 'body' => $this->subject, 'url' => $this->url, 'tag' => $this->kind];
    }
}
