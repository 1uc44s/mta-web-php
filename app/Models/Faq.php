<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;

/** docs/DATABASE.md §10.13 */
final class Faq extends Model
{
    use Publishable;

    protected $fillable = [
        'group', 'question', 'answer', 'show_on_home', 'status', 'published_at', 'sort', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['show_on_home' => 'boolean'];
    }
}
