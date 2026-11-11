<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** docs/DATABASE.md §10.8 */
final class Tag extends Model
{
    public $timestamps = false;

    protected $fillable = ['slug', 'name'];
}
