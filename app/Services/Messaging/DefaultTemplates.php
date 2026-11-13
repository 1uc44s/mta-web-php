<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use InvalidArgumentException;

/**
 * Mesaj şablonlarının v1 kaynağı ve basit, katı değişken yerleştirici (docs/CMS.md §6, ROADMAP 3.7).
 * - Yalnızca anahtarın `variables` listesindeki değişkenler kabul edilir; eksik/fazla/bilinmeyen değişken → istisna.
 * - Çıktı düz metindir; HTML escape'i kanal (MailMessage) yapar.
 * Faz 5: tam TemplateRenderer (filtreler, koşullar) + message_templates tablosu; bu sınıf yedek olarak kalır.
 */
final class DefaultTemplates
{
    private const PATTERN = '/\{\{\s*([a-z_]+(?:\.[a-z_]+)*)\s*\}\}/';

    /**
     * @param  array<string, scalar|null>  $context
     * @return array{subject: string, greeting: string, lines: list<string>, action: array{label: string, url: string}|null, outro: list<string>, salutation: string}
     */
    public static function render(string $key, array $context): array
    {
        $template = trans('messages.'.$key);

        if (! is_array($template) || ! isset($template['variables'], $template['subject'], $template['greeting'], $template['lines'])) {
            throw new InvalidArgumentException("Bilinmeyen mesaj anahtarı: {$key}");
        }

        /** @var list<string> $allowed */
        $allowed = $template['variables'];
        $missing = array_diff($allowed, array_keys($context));
        $extra = array_diff(array_keys($context), $allowed);

        if ($missing !== [] || $extra !== []) {
            throw new InvalidArgumentException("{$key} bağlamı geçersiz. Eksik: [".implode(',', $missing).'] Fazla: ['.implode(',', $extra).']');
        }

        $fill = static fn (string $text): string => (string) preg_replace_callback(
            self::PATTERN,
            static function (array $m) use ($context, $key): string {
                if (! array_key_exists($m[1], $context)) {
                    throw new InvalidArgumentException("{$key} şablonunda izinsiz değişken: {$m[1]}");
                }

                return (string) $context[$m[1]];
            },
            $text,
        );

        /** @var list<string> $lines */
        $lines = $template['lines'];
        /** @var list<string> $outro */
        $outro = $template['outro'] ?? [];
        /** @var array{label: string, url: string}|null $action */
        $action = $template['action'] ?? null;

        return [
            'subject' => mb_substr($fill((string) $template['subject']), 0, 200),
            'greeting' => $fill((string) $template['greeting']),
            'lines' => array_map($fill, $lines),
            'action' => $action === null ? null : ['label' => $fill($action['label']), 'url' => (string) $context[$action['url']]],
            'outro' => array_map($fill, $outro),
            'salutation' => (string) __('messages.salutation', ['name' => config('app.name')]),
        ];
    }
}
