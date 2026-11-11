<?php

declare(strict_types=1);

namespace App\Enums;

/** docs/CMS.md §2 yayın iş akışı. */
enum ContentStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return __('content.status.'.$this->value);
    }
}
