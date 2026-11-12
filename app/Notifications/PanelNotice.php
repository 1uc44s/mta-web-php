<?php

declare(strict_types=1);

namespace App\Notifications;

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
        return ['database'];
    }

    /** @return array<string, string|null> */
    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'title' => $this->title, 'subject' => $this->subject, 'url' => $this->url];
    }
}
