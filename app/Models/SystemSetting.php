<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** docs/DATABASE.md §4.1. Okuma/yazma SettingsRepository üzerinden yapılır. */
final class SystemSetting extends Model
{
    public const CREATED_AT = null;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'group', 'value', 'type', 'is_public', 'is_secret', 'updated_by'];

    protected $hidden = ['value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
            'is_public' => 'boolean',
            'is_secret' => 'boolean',
        ];
    }
}
