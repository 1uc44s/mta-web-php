<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Models\MessageDispatch;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Notifications\TemplatedMailNotification;
use App\Support\EmailMask;
use Illuminate\Support\Facades\Notification;

/**
 * Tüm sistem mesajlarının tek giriş noktası (docs/SRS.md FR-NTF-001/002, docs/CMS.md §6.5).
 * Faz 5: aktif DB şablonu (message_templates) + gönderim logu (message_dispatches). Bağlam, gönderimden ÖNCE
 * şablonun değişken listesine karşı doğrulanır; böylece hatalı bağlam kuyrukta değil çağrı yerinde patlar.
 * Panel/Discord/WhatsApp kanalları ve tercih kontrolü sonraki fazlarda.
 */
final class MessageDispatcher
{
    /** @param  array<string, scalar|null>  $context */
    public function send(string $key, object $recipient, array $context): void
    {
        DefaultTemplates::render($key, $context); // bağlam sözleşmesi (eksik/fazla değişken → istisna)

        $template = MessageTemplate::query()->where(['key' => $key, 'channel' => 'mail', 'locale' => 'tr'])->first();

        $dispatch = MessageDispatch::query()->create([
            'message_template_id' => $template?->id,
            'template_key' => $key,
            'channel' => 'mail',
            'user_id' => $recipient instanceof User ? $recipient->id : null,
            'recipient_masked' => $recipient instanceof User ? EmailMask::mask($recipient->email) : null,
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        Notification::send($recipient, new TemplatedMailNotification($key, $context, $dispatch->id));
    }
}
