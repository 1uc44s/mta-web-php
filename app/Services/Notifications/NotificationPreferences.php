<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Bildirim tercihleri — docs/DATABASE.md §3.11, SRS FR-NTF-003. Kayıt yoksa varsayılan açıktır.
 * Güvenlik/işlem (transactional) mesajları tercihe bağlı değildir ve buradan sorulmaz. Panel bildirimi her zaman yazılır.
 */
final class NotificationPreferences
{
    /** @var array<string, list<string>> kategori → tercihe bağlı kanallar (Faz 10: `push` eklendi) */
    public const MATRIX = [
        'support' => ['mail', 'push'],
        'announcements' => ['mail'],
        'applications' => ['push'],
        'sanctions' => ['push'],
        'payments' => ['push'],
    ];

    public function allows(User $user, string $category, string $channel): bool
    {
        $enabled = DB::table('user_notification_preferences')
            ->where(['user_id' => $user->id, 'category' => $category, 'channel' => $channel])
            ->value('enabled');

        return $enabled === null || (bool) $enabled;
    }

    /** @return array<string, array<string, bool>> kategori → kanal → açık mı */
    public function matrix(User $user): array
    {
        $stored = DB::table('user_notification_preferences')->where('user_id', $user->id)->get();
        $out = [];
        foreach (self::MATRIX as $category => $channels) {
            foreach ($channels as $channel) {
                $row = $stored->first(fn ($r): bool => $r->category === $category && $r->channel === $channel);
                $out[$category][$channel] = $row === null || (bool) $row->enabled;
            }
        }

        return $out;
    }

    /** @param  array<string, array<string, bool>>  $matrix */
    public function save(User $user, array $matrix): void
    {
        foreach (self::MATRIX as $category => $channels) {
            foreach ($channels as $channel) {
                DB::table('user_notification_preferences')->updateOrInsert(
                    ['user_id' => $user->id, 'category' => $category, 'channel' => $channel],
                    ['enabled' => (bool) ($matrix[$category][$channel] ?? false), 'updated_at' => now()],
                );
            }
        }
    }
}
