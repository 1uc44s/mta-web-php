<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Content\ContentWorkflow;
use App\Enums\ContentStatus;
use App\Models\Announcement;
use App\Services\Content\ContentCache;
use App\Services\Content\ContentResourceRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Zamanlanmış içerikleri yayınlar — docs/SRS.md FR-CMS-008, CMS §2 (scheduled → published, published_at <= now).
 * Her dakika çalışır; idempotenttir.
 */
#[Signature('content:publish-scheduled')]
#[Description('Yayın zamanı gelen zamanlanmış içerikleri yayınlar')]
final class PublishScheduledContent extends Command
{
    public function handle(): int
    {
        $total = 0;

        foreach (ContentResourceRegistry::all() as $def) {
            if (! $def['publishable']) {
                continue;
            }

            $def['model']::query()
                ->where('status', ContentStatus::Scheduled->value)
                ->where('published_at', '<=', now())
                ->each(function ($model) use (&$total): void {
                    $model->forceFill(['status' => ContentStatus::Published])->save();
                    if ($model instanceof Announcement) {
                        ContentWorkflow::dispatchAnnouncementEmail($model);
                        ContentWorkflow::dispatchAnnouncementDiscord($model);
                    }
                    $total++;
                });
        }

        if ($total > 0) {
            ContentCache::flush();
        }

        $this->info("Yayınlanan: {$total}");

        return self::SUCCESS;
    }
}
