<?php

declare(strict_types=1);

namespace App\Actions\Content;

use App\Enums\AuditAction;
use App\Enums\ContentStatus;
use App\Jobs\SendAnnouncementEmails;
use App\Models\Announcement;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Content\ContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Yayın iş akışı — docs/CMS.md §2 durum makinesi. Geçersiz geçiş 422 (ValidationException).
 */
final class ContentWorkflow
{
    /** @var array<string, array{from: list<ContentStatus>, to: ContentStatus}> */
    private const TRANSITIONS = [
        'submit-review' => ['from' => [ContentStatus::Draft], 'to' => ContentStatus::InReview],
        'publish' => ['from' => [ContentStatus::Draft, ContentStatus::InReview, ContentStatus::Scheduled], 'to' => ContentStatus::Published],
        'unpublish' => ['from' => [ContentStatus::Published, ContentStatus::Scheduled, ContentStatus::InReview], 'to' => ContentStatus::Draft],
        'archive' => ['from' => [ContentStatus::Published, ContentStatus::Draft], 'to' => ContentStatus::Archived],
        'restore' => ['from' => [ContentStatus::Archived], 'to' => ContentStatus::Draft],
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    public function apply(string $transition, Model $model, User $actor): ContentStatus
    {
        $rule = self::TRANSITIONS[$transition] ?? throw ValidationException::withMessages(['status' => 'Bilinmeyen işlem.']);
        /** @var ContentStatus $current */
        $current = $model->getAttribute('status');

        if (! in_array($current, $rule['from'], true)) {
            throw ValidationException::withMessages(['status' => __('content_admin.errors.transition', ['from' => $current->value, 'action' => $transition])]);
        }

        $to = $rule['to'];
        $attributes = ['status' => $to, 'updated_by' => $actor->id];

        if ($transition === 'publish') {
            $at = $model->getAttribute('published_at');
            if ($at !== null && $at->isFuture()) {
                $to = ContentStatus::Scheduled;
                $attributes['status'] = $to;
            } else {
                $attributes['published_at'] = $at ?? now();
            }
        }

        $model->forceFill($attributes)->save();
        ContentCache::flush();

        $action = match ($transition) {
            'publish' => AuditAction::ContentPublished,
            'unpublish', 'archive' => AuditAction::ContentUnpublished,
            default => null,
        };
        if ($action !== null) {
            $this->audit->record($action, $model, ['before' => ['status' => $current->value], 'after' => ['status' => $to->value]], null, $actor);
        }

        if ($to === ContentStatus::Published && $model instanceof Announcement) {
            self::dispatchAnnouncementEmail($model);
        }

        return $to;
    }

    /** Zamanlayıcı da kullanır: yayında + e-posta kanalı + daha önce gönderilmemiş. */
    public static function dispatchAnnouncementEmail(Announcement $announcement): void
    {
        if (in_array('email', $announcement->channels, true) && $announcement->email_dispatched_at === null) {
            $announcement->forceFill(['email_dispatched_at' => now()])->save();
            SendAnnouncementEmails::dispatch($announcement->id);
        }
    }
}
