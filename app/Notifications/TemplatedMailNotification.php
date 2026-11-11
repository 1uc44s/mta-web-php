<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Services\Messaging\DefaultTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Şablon anahtarıyla gönderilen e-posta (docs/SRS.md FR-NTF-001/005). Her zaman kuyruktan gider;
 * başarısızlıkta 3 deneme, üstel geri çekilme 1 / 5 / 15 dk. Doğrudan kullanılmaz: MessageDispatcher::send().
 */
final class TemplatedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param  array<string, scalar|null>  $context */
    public function __construct(public readonly string $key, public readonly array $context) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = DefaultTemplates::render($this->key, $this->context);

        $mail = (new MailMessage)->subject($t['subject'])->greeting($t['greeting'])->salutation($t['salutation']);

        foreach ($t['lines'] as $line) {
            $mail->line($line);
        }

        if ($t['action'] !== null) {
            $mail->action($t['action']['label'], $t['action']['url']);
        }

        foreach ($t['outro'] as $line) {
            $mail->line($line);
        }

        return $mail;
    }
}
