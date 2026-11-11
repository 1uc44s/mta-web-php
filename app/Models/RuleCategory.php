<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** docs/DATABASE.md §10.12 */
final class RuleCategory extends Model
{
    use Publishable;

    protected $fillable = [
        'slug', 'name', 'description', 'prefix',
        'status', 'published_at', 'sort', 'seo_title', 'meta_description', 'created_by', 'updated_by',
    ];

    /** @return HasMany<Rule, $this> */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }
}
