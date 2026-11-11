<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Bildirim tercihleri — docs/DATABASE.md §3.11, SRS FR-NTF-003. Kayıt yoksa varsayılan açıktır.
 * Güvenlik/işlem (transactional) mesajları tercihe bağlı değildir ve buradan sorulmaz.
 */
final class NotificationPreferences
{
    /** @var list<string> */
    public const CATEGORIES = ['support', 'announcements'];

    /** @var list<string> Faz 6'da yalnızca e-posta tercihe bağlıdır; panel bildirimi her zaman yazılır. */
    public const CHANNELS = ['mail'];

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
        foreach (self::CATEGORIES as $category) {
            foreach (self::CHANNELS as $channel) {
                $row = $stored->first(fn ($r): bool => $r->category === $category && $r->channel === $channel);
                $out[$category][$channel] = $row === null || (bool) $row->enabled;
            }
        }

        return $out;
    }

    /** @param  array<string, array<string, bool>>  $matrix */
    public function save(User $user, array $matrix): void
    {
        foreach (self::CATEGORIES as $category) {
            foreach (self::CHANNELS as $channel) {
                DB::table('user_notification_preferences')->updateOrInsert(
                    ['user_id' => $user->id, 'category' => $category, 'channel' => $channel],
                    ['enabled' => (bool) ($matrix[$category][$channel] ?? false), 'updated_at' => now()],
                );
            }
        }
    }
}
