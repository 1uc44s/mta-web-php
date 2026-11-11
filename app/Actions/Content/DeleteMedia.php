<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Medya silme — docs/ROUTES.md §6.8: kullanımdaysa 409. Dosyalar (orijinal + varyantlar) diskten de silinir.
 */
final class DeleteMedia
{
    /** @var array<string, list<string>> */
    private const REFERENCES = [
        'pages' => ['og_media_id'],
        'news_posts' => ['cover_media_id'],
        'game_systems' => ['media_id'],
        'game_jobs' => ['media_id'],
        'factions' => ['logo_media_id', 'cover_media_id'],
        'community_events' => ['cover_media_id'],
        'academy_courses' => ['cover_media_id'],
    ];

    public function inUse(Media $media): bool
    {
        foreach (self::REFERENCES as $table => $columns) {
            foreach ($columns as $column) {
                if (DB::table($table)->where($column, $media->id)->exists()) {
                    return true;
                }
            }
        }

        return DB::table('page_sections')->whereJsonContains('data->media_ids', $media->id)->exists();
    }

    public function handle(Media $media): bool
    {
        if ($this->inUse($media)) {
            return false;
        }

        $disk = Storage::disk($media->disk);
        $disk->delete([$media->path, ...array_values($media->variants ?? [])]);
        $media->delete();

        return true;
    }
}
