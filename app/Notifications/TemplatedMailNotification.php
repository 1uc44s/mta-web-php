<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\MessageDispatch;
use App\Models\MessageTemplate;
use App\Services\Content\MarkdownRenderer;
use App\Services\Messaging\DefaultTemplates;
use App\Services\Messaging\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Şablon anahtarıyla gönderilen e-posta (docs/SRS.md FR-NTF-001/005, CMS §6.5). Her zaman kuyruktan gider;
 * başarısızlıkta 3 deneme, üstel geri çekilme 1 / 5 / 15 dk. Doğrudan kullanılmaz: MessageDispatcher::send().
 *
 * Kaynak sırası: aktif DB şablonu (key + mail + tr) → render hatasında kodda gömülü varsayılan (kritik mesaj kaybolmaz).
 */
final class TemplatedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @param  array<string, scalar|null>  $context */
    public function __construct(
        public readonly string $key,
        public readonly array $context,
        public readonly ?int $dispatchId = null,
    ) {}

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
        $mail = $this->fromDatabase() ?? $this->fromDefaults();
        $this->markDispatch('sent');

        return $mail;
    }

    public function failed(Throwable $e): void
    {
        $this->markDispatch('failed', mb_substr($e->getMessage(), 0, 500));
    }

    private function fromDatabase(): ?MailMessage
    {
        $template = MessageTemplate::query()
            ->where(['key' => $this->key, 'channel' => 'mail', 'locale' => 'tr', 'status' => 'active'])
            ->first();

        if ($template === null) {
            return null;
        }

        try {
            $renderer = app(TemplateRenderer::class);
            $subject = $renderer->render((string) $template->subject, $this->context, $template->variables, 'text');
            $body = $renderer->render($template->body, $this->context, $template->variables, 'markdown');

            return (new MailMessage)
                ->subject(mb_substr(trim($subject), 0, 200))
                ->markdown('mail.templated', ['body' => app(MarkdownRenderer::class)->render($body)]);
        } catch (Throwable $e) {
            Log::warning('Mesaj şablonu render edilemedi, varsayılan kullanılıyor', ['key' => $this->key, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function fromDefaults(): MailMessage
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

    private function markDispatch(string $status, ?string $error = null): void
    {
        if ($this->dispatchId === null) {
            return;
        }

        MessageDispatch::query()->whereKey($this->dispatchId)->increment('attempts', 1, [
            'status' => $status,
            'error' => $error,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
