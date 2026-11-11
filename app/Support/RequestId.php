<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

/**
 * İstek korelasyon kimliği. Context'e yazıldığı için tüm log kayıtlarına otomatik eklenir.
 */
final class RequestId
{
    public const HEADER = 'X-Request-Id';

    private const CONTEXT_KEY = 'request_id';

    /** Dışarıdan gelen kimlik yalnızca bu biçimdeyse kabul edilir (log enjeksiyonunu önler). */
    private const PATTERN = '/^[A-Za-z0-9_-]{8,64}$/';

    public static function assign(?string $incoming): string
    {
        $id = is_string($incoming) && preg_match(self::PATTERN, $incoming) === 1
            ? $incoming
            : (string) Str::ulid();

        Context::add(self::CONTEXT_KEY, $id);

        return $id;
    }

    public static function current(): ?string
    {
        $id = Context::get(self::CONTEXT_KEY);

        return is_string($id) ? $id : null;
    }
}
