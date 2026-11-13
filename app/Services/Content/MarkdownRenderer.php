<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Support\Brand;
use App\Support\SafeHtml;
use Illuminate\Support\Facades\Cache;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Markdown → güvenli HTML (docs/CMS.md §3.2, docs/SRS.md FR-CMS-004).
 * İki katman: CommonMark ham HTML'i escape eder, ardından Symfony sanitizer allowlist uygular.
 */
final class MarkdownRenderer
{
    private ?MarkdownConverter $converter = null;

    private ?HtmlSanitizer $sanitizer = null;

    public function render(?string $markdown): SafeHtml
    {
        $markdown = Brand::fill(trim((string) $markdown)); // {site} / {site_short} (docs/CMS.md Ek)

        if ($markdown === '') {
            return new SafeHtml('');
        }

        $html = Cache::rememberForever('md:v1:'.hash('sha256', $markdown), fn (): string => $this->sanitizer()->sanitize(
            $this->converter()->convert($markdown)->getContent(),
        ));

        return new SafeHtml($html);
    }

    /** Düz metin özet (meta description, kart özeti). */
    public function excerpt(?string $markdown, int $limit = 160): string
    {
        $text = strip_tags($this->render($markdown)->toHtml());

        return str((string) preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')))
            ->trim()
            ->limit($limit)
            ->toString();
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter instanceof MarkdownConverter) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 10,
            'max_delimiters_per_line' => 200,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new AutolinkExtension);
        $environment->addExtension(new StrikethroughExtension);
        $environment->addExtension(new TableExtension);

        return $this->converter = new MarkdownConverter($environment);
    }

    private function sanitizer(): HtmlSanitizer
    {
        if ($this->sanitizer instanceof HtmlSanitizer) {
            return $this->sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowElement('p')->allowElement('br')->allowElement('hr')
            ->allowElement('h2')->allowElement('h3')->allowElement('h4')
            ->allowElement('ul')->allowElement('ol')->allowElement('li')
            ->allowElement('strong')->allowElement('em')->allowElement('del')
            ->allowElement('blockquote')->allowElement('code')->allowElement('pre')
            ->allowElement('table')->allowElement('thead')->allowElement('tbody')
            ->allowElement('tr')->allowElement('th')->allowElement('td')
            ->allowElement('a', ['href', 'title'])
            ->allowElement('img', ['src', 'alt', 'width', 'height'])
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['https'])
            ->allowRelativeMedias()
            ->forceAttribute('a', 'rel', 'noopener nofollow ugc')
            ->withMaxInputLength(200_000);

        return $this->sanitizer = new HtmlSanitizer($config);
    }
}
