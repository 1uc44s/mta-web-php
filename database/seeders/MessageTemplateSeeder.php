<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MessageTemplate;
use App\Services\Messaging\TemplateRenderer;
use Illuminate\Database\Seeder;

/**
 * Sistem mesaj şablonları (docs/CMS.md §6.4, DATABASE §12). Kaynak: lang/tr/messages.php (kodda gömülü varsayılanlar).
 * İdempotent: mevcut şablonun gövdesine dokunmaz (ACP düzenlemeleri korunur); yalnızca `variables` listesini senkronlar.
 */
final class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $renderer = app(TemplateRenderer::class);

        foreach ((array) trans('messages') as $key => $template) {
            if (! is_array($template) || ! isset($template['variables'])) {
                continue;
            }

            /** @var list<string> $variables */
            $variables = $template['variables'];
            $body = self::markdownBody($template);
            $renderer->parse($body, $variables); // seed edilen gövde de geçerli olmalı

            $existing = MessageTemplate::query()->where(['key' => $key, 'channel' => 'mail', 'locale' => 'tr'])->first();

            if ($existing !== null) {
                $existing->update(['variables' => $variables]);

                continue;
            }

            MessageTemplate::query()->create([
                'key' => $key, 'channel' => 'mail', 'locale' => 'tr', 'name' => (string) $template['subject'],
                'subject' => (string) $template['subject'], 'body' => $body, 'variables' => $variables,
                'status' => 'active', 'is_system' => true, 'version' => 1,
            ]);
        }
    }

    /** @param  array<string, mixed>  $t */
    public static function markdownBody(array $t): string
    {
        $parts = [(string) $t['greeting']];
        foreach ((array) $t['lines'] as $line) {
            $parts[] = (string) $line;
        }
        if (isset($t['action']) && is_array($t['action'])) {
            $parts[] = '**['.$t['action']['label'].']({{ '.$t['action']['url'].' }})**';
        }
        foreach ((array) ($t['outro'] ?? []) as $line) {
            $parts[] = (string) $line;
        }
        $parts[] = (string) __('messages.salutation');

        return implode("\n\n", $parts);
    }
}
