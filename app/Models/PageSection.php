<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** docs/DATABASE.md §10.3, docs/CMS.md §4 */
final class PageSection extends Model
{
    protected $fillable = ['page_id', 'type', 'data', 'sort', 'is_visible'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_visible' => 'boolean',
        ];
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
