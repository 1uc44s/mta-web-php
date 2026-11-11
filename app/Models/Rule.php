<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** docs/DATABASE.md §10.12 */
final class Rule extends Model
{
    use Publishable;

    protected $fillable = [
        'rule_category_id', 'code', 'title', 'body', 'severity_hint',
        'status', 'published_at', 'sort', 'created_by', 'updated_by',
    ];

    /** @return BelongsTo<RuleCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RuleCategory::class, 'rule_category_id');
    }

    /** "RP-4.2" → "rp-4-2" (HTML id ve URL çapası). */
    public function anchor(): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '-', strtolower($this->code));
    }
}
