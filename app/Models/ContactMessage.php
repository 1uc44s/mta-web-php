<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;

/** docs/DATABASE.md §10.16 */
final class ContactMessage extends Model
{
    use HasPublicUlid;

    protected $fillable = ['name', 'email', 'subject', 'body', 'ip_hash', 'status', 'handled_by'];
}
