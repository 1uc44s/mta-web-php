<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Duyuru akışı — docs/SRS.md FR-ADM-011, DATABASE §10.15. Site bandı herkese (audience=all) ve 60 sn cache'li dizi;
 * panel listesi kullanıcıya göre sorgu anında hesaplanır (fan-out yok).
 */
final class AnnouncementFeed
{
    public const BANNER_CACHE = 'announcements:banner:v1';

    /** @return list<array{ulid: string, title: string, priority: string}> */
    public function banner(): array
    {
        /** @var list<array{ulid: string, title: string, priority: string}> */
        return Cache::remember(self::BANNER_CACHE, 60, fn (): array => Announcement::query()
            ->activeOn('public_banner')
            ->where('audience', 'all')
            ->limit(1)
            ->get(['ulid', 'title', 'priority'])
            ->map(fn (Announcement $a): array => ['ulid' => $a->ulid, 'title' => $a->title, 'priority' => $a->priority])
            ->all());
    }

    /** @return Collection<int, Announcement> Panel duyuruları (okunmamışlar önce). */
    public function forUser(User $user, int $limit = 5): Collection
    {
        $read = $user->belongsToMany(Announcement::class, 'announcement_reads')->pluck('announcements.id')->all();

        return Announcement::query()
            ->activeOn('panel')
            ->whereIn('audience', ['all', 'players'])
            ->limit($limit)
            ->get()
            ->each(fn (Announcement $a) => $a->setAttribute('is_read', in_array($a->id, $read, true))
                ->setAttribute('html', app(MarkdownRenderer::class)->render($a->body)))
            ->sortBy(fn (Announcement $a): int => $a->getAttribute('is_read') ? 1 : 0)
            ->values();
    }
}
