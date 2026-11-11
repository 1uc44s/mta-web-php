<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ticket eki — docs/DATABASE.md §8.4. Özel diskte (`local` = storage/app/private); indirme yetki kontrollü.
 *
 * @property int $id
 * @property string $ulid
 * @property int $ticket_id
 * @property int $message_id
 * @property int $uploaded_by
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size_bytes
 * @property string $sha256
 */
final class SupportAttachment extends Model
{
    use HasPublicUlid;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime', 'size_bytes' => 'integer'];
    }

    /** @return BelongsTo<SupportMessage, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'message_id');
    }

    /** @return BelongsTo<SupportTicket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }
}
