<?php

declare(strict_types=1);

use App\Services\Content\MarkdownRenderer;
use App\Services\Messaging\TemplateRenderer;
use App\Services\Messaging\TemplateSyntaxException;

/* docs/CMS.md §6.2–6.3 — şablon motoru: söz dizimi, filtreler, SSTI reddi, kanal escape. */

function tpl(): TemplateRenderer
{
    return new TemplateRenderer;
}

it('CMS-6.2: değişken, filtre ve tek seviye koşul render edilir', function (): void {
    $out = tpl()->render(
        'Merhaba {{ user.username | upper }}{{#if ticket.number}} · #{{ ticket.number }}{{/if}} · {{ note | default("—") }} · {{ long | truncate(5) }}',
        ['user.username' => 'kaan', 'ticket.number' => null, 'note' => '', 'long' => 'abcdefghij'],
        ['user.username', 'ticket.number', 'note', 'long'],
        'text',
    );

    expect($out)->toBe('Merhaba KAAN · — · abcd…');
});

it('CMS-6.2: money ve number filtreleri Türkçe biçim üretir', function (): void {
    expect(tpl()->render('{{ a | money }} / {{ b | number }}', ['a' => 125000, 'b' => 1250000], ['a', 'b'], 'text'))
        ->toBe('1.250,00 ₺ / 1.250.000');
});

it('CMS-6.2: SSTI ve geçersiz söz dizimi kayıt anında reddedilir', function (string $template): void {
    tpl()->parse($template, ['user.username']);
})->throws(TemplateSyntaxException::class)->with([
    'fonksiyon çağrısı' => ["{{ system('id') }}"],
    'ifade' => ['{{ 1 + 1 }}'],
    'bilinmeyen değişken' => ['{{ user.email }}'],
    'üçlü parantez' => ['{{{ user.username }}}'],
    'iç içe if' => ['{{#if user.username}}{{#if user.username}}x{{/if}}{{/if}}'],
    'kapanmamış if' => ['{{#if user.username}} x'],
    'eşleşmeyen endif' => ['x {{/if}}'],
    'kapanmamış etiket' => ['{{ user.username'],
    'bilinmeyen filtre' => ['{{ user.username | exec }}'],
    'php nesne erişimi' => ['{{ user.username->password }}'],
    'blade yönergesi değişkende' => ['{{ @php }}'],
    '20 KB üstü' => [str_repeat('a', 20481)],
]);

it('CMS-6.3: Blade/PHP benzeri metinler yalnızca düz metin olarak kalır (çalıştırılmaz)', function (): void {
    $md = tpl()->render('@php echo 1; @endphp {!! $x !!} <script>alert(1)</script>', [], [], 'markdown');
    $html = app(MarkdownRenderer::class)->render($md)->toHtml();

    expect($html)->not->toContain('<script')->and($html)->toContain('php echo 1');
});

it('CMS-6.3: değişken değeri Markdown/HTML enjekte edemez; link.* yalnızca http(s)', function (): void {
    $md = tpl()->render('Merhaba {{ user.username }} [Aç]({{ link.x }})', [
        'user.username' => '[tıkla](javascript:alert(1)) <img src=x onerror=alert(1)> **kalın**',
        'link.x' => 'javascript:alert(1)',
    ], ['user.username', 'link.x'], 'markdown');

    $html = app(MarkdownRenderer::class)->render($md)->toHtml();

    expect($html)->not->toMatch('/href=["\']?javascript/i')
        ->and(substr_count($html, '<a '))->toBeLessThanOrEqual(1) // yalnızca şablonun kendi (boş) bağlantısı
        ->and($html)->not->toContain('<img')
        ->and($html)->not->toContain('<strong>')
        ->and($html)->not->toMatch('/<[^>]+onerror=/');
});
