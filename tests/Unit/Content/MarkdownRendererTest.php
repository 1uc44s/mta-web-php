<?php

declare(strict_types=1);

use App\Services\Content\MarkdownRenderer;

/*
 * docs/SRS.md FR-CMS-004, SEC-022: Markdown çıktısı XSS taşımaz.
 */

it('FR-CMS-004: XSS vektörleri temizlenir', function (string $input): void {
    $html = app(MarkdownRenderer::class)->render($input)->toHtml();

    // Tehdit: çalıştırılabilir etiket/öznitelik. Kaçışlı düz metin ("&lt;a href…") zararsızdır.
    expect(strtolower($html))
        ->not->toContain('<script')
        ->not->toContain('href="javascript')
        ->not->toContain('src="javascript')
        ->not->toMatch('/<[a-z]+[^>]*\son[a-z]+=/')
        ->not->toContain('<iframe')
        ->not->toContain('<svg');
})->with([
    '<script>alert(1)</script>',
    '<img src=x onerror=alert(1)>',
    '[tıkla](javascript:alert(1))',
    '[tıkla](JaVaScRiPt:alert(1))',
    '<iframe src="https://evil.example"></iframe>',
    '<svg onload=alert(1)>',
    '![x](javascript:alert(1))',
    '<a href="javascript:alert(1)">x</a>',
]);

it('FR-CMS-004: izinli biçimlendirme korunur ve dış bağlantılara rel eklenir', function (): void {
    $html = app(MarkdownRenderer::class)->render("## Başlık\n\n**kalın** [bağlantı](https://example.com)\n\n- madde")->toHtml();

    expect($html)->toContain('<h2>Başlık</h2>')
        ->toContain('<strong>kalın</strong>')
        ->toContain('rel="noopener nofollow ugc"')
        ->toContain('<li>madde</li>');
});

it('FR-CMS-004: boş girdi boş çıktı verir', function (): void {
    expect(app(MarkdownRenderer::class)->render(null)->isEmpty())->toBeTrue();
});
