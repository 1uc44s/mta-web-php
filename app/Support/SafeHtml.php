<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Support\Htmlable;
use Stringable;

/**
 * Sanitize edilmiş HTML (docs/RULES.md §6). Yalnızca MarkdownRenderer üretir.
 * Htmlable olduğu için Blade'de `{{ $html }}` ile kaçışsız basılır; `{!! !!}` gerekmez.
 */
final readonly class SafeHtml implements Htmlable, Stringable
{
    /** @internal Yalnızca App\Services\Content\MarkdownRenderer tarafından oluşturulur. */
    public function __construct(private string $html) {}

    public function toHtml(): string
    {
        return $this->html;
    }

    public function __toString(): string
    {
        return $this->html;
    }

    public function isEmpty(): bool
    {
        return trim($this->html) === '';
    }
}
