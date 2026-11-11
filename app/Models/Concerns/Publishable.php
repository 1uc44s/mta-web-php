<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Yayın bloğu taşıyan modeller (docs/DATABASE.md §10, docs/CMS.md §2).
 * Kolonlar App\Support\Database\ContentColumns::publishable() ile oluşturulur.
 *
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 * @property int $sort
 * @property string|null $seo_title
 * @property string|null $meta_description
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @mixin Model
 */
trait Publishable
{
    public function initializePublishable(): void
    {
        $this->mergeCasts([
            'status' => ContentStatus::class,
            'published_at' => 'immutable_datetime',
        ]);
    }

    /**
     * Yalnızca public'te görünmesi gereken kayıtlar.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyColumn('status'), ContentStatus::Published->value)
            ->whereNotNull($this->qualifyColumn('published_at'))
            ->where($this->qualifyColumn('published_at'), '<=', now());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy($this->qualifyColumn('sort'))->orderBy($this->getQualifiedKeyName());
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }
}
