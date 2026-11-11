<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Mesaj şablon motoru — docs/CMS.md §6.2–6.3. Küçük bir tokenizer + düz AST; regex ile kör değiştirme değildir.
 *
 * Söz dizimi:  {{ yol.ad }}  ·  {{ yol | filtre | filtre(arg) }}  ·  {{#if yol}} … {{/if}} (tek seviye)
 * - Değişkenler yalnızca allowlist'ten; bilinmeyen değişken/filtre, `{{{`, iç içe koşul, ifade → TemplateSyntaxException.
 * - Kod çalıştırma yoktur: değerler yalnızca bağlam dizisinden okunur ve dönüştürülür.
 * - Escape kanal bazındadır: `markdown` (e-posta gövdesi) Markdown/HTML özel karakterlerini kaçırır; `text` kaçırmaz
 *   (panel çıktısı Blade'de escape edilir). `link.*` değişkenleri yalnızca http(s) URL olabilir ve kaçırılmaz.
 */
final class TemplateRenderer
{
    public const MAX_BYTES = 20480;

    private const MAX_MS = 50.0;

    /** @var list<string> */
    public const FILTERS = ['upper', 'lower', 'title', 'date', 'datetime', 'relative', 'number', 'money', 'truncate', 'default'];

    private const PATH = '[a-z_][a-z0-9_]*(?:\.[a-z_][a-z0-9_]*)*';

    /**
     * Şablonu ayrıştırır ve allowlist'e karşı doğrular (kayıt anında). Geçersizse istisna.
     *
     * @param  list<string>  $allowed
     * @return list<array{type: string, value?: string, path?: string, filters?: list<array{name: string, arg: string|int|null}>}>
     */
    public function parse(string $template, array $allowed): array
    {
        if (strlen($template) > self::MAX_BYTES) {
            throw new TemplateSyntaxException('Şablon en fazla 20 KB olabilir.');
        }

        if (str_contains($template, '{{{') || str_contains($template, '}}}')) {
            throw new TemplateSyntaxException('Üçlü süslü parantez ({{{ }}}) desteklenmez; ham çıktı yoktur.');
        }

        $nodes = [];
        $offset = 0;
        $inIf = false;
        $length = strlen($template);

        while ($offset < $length) {
            $open = strpos($template, '{{', $offset);

            if ($open === false) {
                $nodes[] = ['type' => 'text', 'value' => substr($template, $offset)];
                break;
            }

            if ($open > $offset) {
                $nodes[] = ['type' => 'text', 'value' => substr($template, $offset, $open - $offset)];
            }

            $close = strpos($template, '}}', $open + 2);
            if ($close === false) {
                throw new TemplateSyntaxException('Kapanmamış {{ etiketi.');
            }

            $inner = trim(substr($template, $open + 2, $close - $open - 2));
            $offset = $close + 2;

            if (preg_match('/^#if\s+('.self::PATH.')$/', $inner, $m) === 1) {
                if ($inIf) {
                    throw new TemplateSyntaxException('İç içe {{#if}} desteklenmez.');
                }
                $this->assertAllowed($m[1], $allowed);
                $nodes[] = ['type' => 'if', 'path' => $m[1]];
                $inIf = true;

                continue;
            }

            if ($inner === '/if') {
                if (! $inIf) {
                    throw new TemplateSyntaxException('Eşleşmeyen {{/if}}.');
                }
                $nodes[] = ['type' => 'endif'];
                $inIf = false;

                continue;
            }

            $nodes[] = $this->parseVariable($inner, $allowed);
        }

        if ($inIf) {
            throw new TemplateSyntaxException('Kapanmamış {{#if}}.');
        }

        return $nodes;
    }

    /**
     * @param  array<string, scalar|null>  $context
     * @param  list<string>  $allowed
     */
    public function render(string $template, array $context, array $allowed, string $escape = 'markdown'): string
    {
        $started = microtime(true);
        $nodes = $this->parse($template, $allowed);
        $out = '';
        $skip = false;

        foreach ($nodes as $node) {
            if ((microtime(true) - $started) * 1000 > self::MAX_MS) {
                throw new TemplateSyntaxException('Şablon render süresi sınırı aşıldı.');
            }

            switch ($node['type']) {
                case 'if':
                    $value = $context[$node['path'] ?? ''] ?? null;
                    $skip = $value === null || $value === '' || $value === false;
                    break;
                case 'endif':
                    $skip = false;
                    break;
                case 'text':
                    $out .= $skip ? '' : ($node['value'] ?? '');
                    break;
                default:
                    if (! $skip) {
                        $out .= $this->value($node, $context, $escape);
                    }
            }
        }

        return $out;
    }

    /**
     * @param  list<string>  $allowed
     * @return array{type: string, path: string, filters: list<array{name: string, arg: string|int|null}>}
     */
    private function parseVariable(string $inner, array $allowed): array
    {
        $parts = array_map('trim', explode('|', $inner));
        $path = array_shift($parts);

        if (preg_match('/^'.self::PATH.'$/', (string) $path) !== 1) {
            throw new TemplateSyntaxException('Geçersiz ifade: {{ '.mb_substr($inner, 0, 60).' }}. Yalnızca değişken adı ve filtre kullanılabilir.');
        }

        $this->assertAllowed((string) $path, $allowed);

        $filters = [];
        foreach ($parts as $part) {
            if (preg_match('/^([a-z]+)(?:\((?:(\d{1,4})|"([^"\\\\]{0,80})")\))?$/', $part, $m) !== 1 || ! in_array($m[1], self::FILTERS, true)) {
                throw new TemplateSyntaxException('Bilinmeyen veya hatalı filtre: '.mb_substr($part, 0, 40));
            }
            $arg = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : ($m[3] ?? null);
            if ($m[1] === 'truncate' && ! is_int($arg)) {
                throw new TemplateSyntaxException('truncate filtresi sayı ister: truncate(80)');
            }
            $filters[] = ['name' => $m[1], 'arg' => $arg];
        }

        return ['type' => 'var', 'path' => (string) $path, 'filters' => $filters];
    }

    /** @param  list<string>  $allowed */
    private function assertAllowed(string $path, array $allowed): void
    {
        if (! in_array($path, $allowed, true)) {
            throw new TemplateSyntaxException("İzin verilmeyen değişken: {$path}. Kullanılabilir: ".implode(', ', $allowed));
        }
    }

    /**
     * @param  array{path?: string, filters?: list<array{name: string, arg: string|int|null}>}  $node
     * @param  array<string, scalar|null>  $context
     */
    private function value(array $node, array $context, string $escape): string
    {
        $path = $node['path'] ?? '';
        $value = $context[$path] ?? null;

        foreach ($node['filters'] ?? [] as $filter) {
            $value = $this->applyFilter($filter['name'], $filter['arg'], $value);
        }

        $string = $value === null ? '' : (string) $value;

        if (str_starts_with($path, 'link.')) {
            return preg_match('#^https?://[^\s<>"]+$#', $string) === 1 ? $string : '';
        }

        return $escape === 'markdown' ? self::escapeMarkdown($string) : $string;
    }

    private function applyFilter(string $name, string|int|null $arg, mixed $value): mixed
    {
        try {
            return match ($name) {
                'upper' => mb_strtoupper((string) $value),
                'lower' => mb_strtolower((string) $value),
                'title' => mb_convert_case((string) $value, MB_CASE_TITLE),
                'date' => $value === null ? null : CarbonImmutable::parse((string) $value)->locale('tr')->translatedFormat('j F Y'),
                'datetime' => $value === null ? null : CarbonImmutable::parse((string) $value)->locale('tr')->translatedFormat('j F Y, H:i'),
                'relative' => $value === null ? null : CarbonImmutable::parse((string) $value)->locale('tr')->diffForHumans(),
                'number' => number_format((float) $value, 0, ',', '.'),
                'money' => number_format(((int) $value) / 100, 2, ',', '.').' ₺',
                'truncate' => mb_strimwidth((string) $value, 0, (int) $arg, '…'),
                'default' => $value === null || $value === '' ? $arg : $value,
                default => $value,
            };
        } catch (Throwable) {
            return $value;
        }
    }

    /** Markdown ve HTML özel karakterlerini kaçırır (değişken değeri biçimlendirme/HTML enjekte edemez). */
    public static function escapeMarkdown(string $value): string
    {
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return (string) preg_replace('/([\\\\`*_{}\[\]()#+\-.!|~>])/', '\\\\$1', $value);
    }
}
