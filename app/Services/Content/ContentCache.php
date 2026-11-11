<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\View\Composers\PublicLayoutComposer;
use Illuminate\Support\Facades\Cache;

/**
 * İçerik önbellek geçersizleştirme — docs/CMS.md §7. Markdown cache'i içerik hash'li olduğu için temizlenmez.
 */
final class ContentCache
{
    public static function flush(): void
    {
        Cache::forget('sitemap:v1');
        Cache::forget('stats-strip:v1');
        Cache::forget(PublicLayoutComposer::CACHE_KEY);
        Cache::forget('announcements:banner:v1');
    }
}
