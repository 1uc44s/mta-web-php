<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Başvuru türü — docs/DATABASE.md §9.1, SRS FR-APP-001. Form alanları `App\Support\ApplicationForms` içindeki sabit şemadan.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $kind
 * @property string $form_key
 * @property string|null $description
 * @property int|null $faction_id
 * @property int $branch_id
 * @property bool $is_open
 * @property bool $requires_linked_account
 * @property bool $requires_character
 * @property int $min_account_age_days
 * @property int $cooldown_days
 */
final class ApplicationType extends Model
{
    /** @var list<string> */
    public const KINDS = ['faction', 'staff', 'special_role', 'event', 'whitelist'];

    protected $fillable = [
        'slug', 'name', 'kind', 'form_key', 'description', 'faction_id', 'branch_id', 'is_open', 'requires_linked_account',
        'requires_character', 'min_account_age_days', 'cooldown_days', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean', 'requires_linked_account' => 'boolean', 'requires_character' => 'boolean',
            'min_account_age_days' => 'integer', 'cooldown_days' => 'integer', 'sort' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Faction, $this> */
    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class);
    }
}
