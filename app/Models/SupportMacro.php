<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Yanıt makrosu — docs/DATABASE.md §8.6, SRS FR-SUP-009. Gövde şablon motoru söz dizimindedir.
 *
 * @property int $id
 * @property string $title
 * @property string $body
 * @property int|null $category_id
 * @property int|null $branch_id
 * @property bool $is_active
 */
final class SupportMacro extends Model
{
    /** @var list<string> Makrolarda kullanılabilir değişkenler (FR-SUP-009). */
    public const VARIABLES = ['player.username', 'ticket.number', 'ticket.subject', 'staff.username'];

    protected $fillable = ['title', 'body', 'category_id', 'branch_id', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
