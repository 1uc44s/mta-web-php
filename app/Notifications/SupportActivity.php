<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Notifications\Notification;

/**
 * Panel bildirimi (database kanalı) — docs/SRS.md FR-SUP-010, FR-UCP-002.
 * `staff_replied`: oyuncuya (yetkili public yanıtı) · `player_messaged`: atanan yetkiliye (oyuncu mesajı).
 * Veri yalnızca gösterim alanları içerir; mesaj gövdesi bildirime kopyalanmaz.
 */
final class SupportActivity extends Notification
{
    public function __construct(private readonly SupportTicket $ticket, private readonly string $kind) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => $this->kind === 'staff_replied'
                ? "Destek talebine yanıt geldi: {$this->ticket->number}"
                : "Oyuncu yanıt yazdı: {$this->ticket->number}",
            'subject' => $this->ticket->subject,
            'url' => $this->kind === 'staff_replied'
                ? route('panel.support.show', $this->ticket)
                : route('admin.support.show', $this->ticket),
        ];
    }
}
