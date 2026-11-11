<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Notifications\TemplatedMailNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Tüm sistem mesajlarının tek giriş noktası (docs/SRS.md FR-NTF-001, docs/CMS.md §6.5).
 * v1 (Faz 3): yalnızca `mail` kanalı + kodda gömülü şablonlar. Şablon bağlamı burada, gönderimden ÖNCE doğrulanır;
 * böylece hatalı bağlam kuyrukta değil çağrı yerinde patlar.
 * Faz 5: message_templates, message_dispatches logu, panel kanalı, tercih/rıza kontrolü.
 */
final class MessageDispatcher
{
    /** @param  array<string, scalar|null>  $context */
    public function send(string $key, object $recipient, array $context): void
    {
        DefaultTemplates::render($key, $context); // doğrulama

        Notification::send($recipient, new TemplatedMailNotification($key, $context));
    }
}
