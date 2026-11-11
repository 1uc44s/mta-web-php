<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Enums\AuditAction;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Messaging\TemplateRenderer;
use App\Services\Messaging\TemplateSyntaxException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mesaj şablonu kaydı — docs/CMS.md §6.2: söz dizimi + değişken allowlist doğrulaması KAYIT ANINDA yapılır.
 * Önceki sürüm revizyona yazılır, sürüm +1, audit `templates.updated`.
 */
final class SaveMessageTemplate
{
    public function __construct(private readonly TemplateRenderer $renderer, private readonly AuditLogger $audit) {}

    public function handle(MessageTemplate $template, ?string $subject, string $body, User $actor): void
    {
        try {
            $this->renderer->parse((string) $subject, $template->variables);
        } catch (TemplateSyntaxException $e) {
            throw ValidationException::withMessages(['subject' => $e->getMessage()]);
        }

        try {
            $this->renderer->parse($body, $template->variables);
        } catch (TemplateSyntaxException $e) {
            throw ValidationException::withMessages(['body' => $e->getMessage()]);
        }

        DB::transaction(function () use ($template, $subject, $body, $actor): void {
            $template->revisions()->create([
                'version' => $template->version, 'subject' => $template->subject, 'body' => $template->body, 'changed_by' => $actor->id,
            ]);
            $template->update(['subject' => $subject, 'body' => $body, 'version' => $template->version + 1, 'updated_by' => $actor->id]);
        });

        $this->audit->record(AuditAction::TemplatesUpdated, $template, ['after' => ['key' => $template->key, 'version' => $template->version]], null, $actor);
    }

    /**
     * Önizleme / test gönderimi için örnek bağlam (CMS §6.5 `sample()`).
     *
     * @param  list<string>  $variables
     * @return array<string, string|int>
     */
    public static function sampleContext(array $variables): array
    {
        $out = [];
        foreach ($variables as $var) {
            $out[$var] = match (true) {
                str_starts_with($var, 'link.') => rtrim((string) config('app.url'), '/').'/ornek',
                $var === 'user.username' => 'ornek_oyuncu',
                $var === 'expires_minutes' => 60,
                str_ends_with($var, '.time'), str_ends_with($var, '_at') => now()->format('d.m.Y H:i'),
                default => 'Örnek '.str_replace(['.', '_'], ' ', $var),
            };
        }

        return $out;
    }
}
