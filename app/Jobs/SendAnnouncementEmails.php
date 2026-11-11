<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\UserStatus;
use App\Models\Announcement;
use App\Models\User;
use App\Services\Content\MarkdownRenderer;
use App\Services\Messaging\MessageDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Duyuru e-posta kanalı — docs/SRS.md FR-ADM-011: yayında bir kez, doğrulanmış ve aktif hesaplara, 200'lük parçalarla.
 * Fan-out satırı açılmaz; her alıcı için message_dispatches kaydı MessageDispatcher tarafından yazılır.
 */
final class SendAnnouncementEmails implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $announcementId) {}

    public function handle(MessageDispatcher $messages, MarkdownRenderer $markdown): void
    {
        $announcement = Announcement::query()->find($this->announcementId);

        if ($announcement === null || ! $announcement->isPublished()) {
            return;
        }

        $excerpt = $markdown->excerpt($announcement->body, 280);

        User::query()
            ->whereNotNull('email_verified_at')
            ->where('status', UserStatus::Active->value)
            ->select(['id', 'username', 'email'])
            ->chunkById(200, function ($users) use ($messages, $announcement, $excerpt): void {
                foreach ($users as $user) {
                    $messages->send('announcements.published', $user, [
                        'user.username' => $user->username,
                        'announcement.title' => $announcement->title,
                        'announcement.excerpt' => $excerpt,
                        'link.announcement' => route('panel.dashboard'),
                    ]);
                }
            });
    }
}
