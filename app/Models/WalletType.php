<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cüzdan türü — docs/DATABASE.md §6.1, OD-03 (varsayılan: yalnızca `web_credit`).
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $unit_label
 * @property bool $is_purchasable
 * @property bool $is_active
 */
final class WalletType extends Model
{
    public const WEB_CREDIT = 'web_credit';

    protected $fillable = ['key', 'name', 'unit_label', 'is_purchasable', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['is_purchasable' => 'boolean', 'is_active' => 'boolean'];
    }
}
