<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use InvalidArgumentException;

/** Şablon söz dizimi veya değişken allowlist ihlali (docs/CMS.md §6.2). Mesaj editöre gösterilir. */
final class TemplateSyntaxException extends InvalidArgumentException {}
