<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Gönderim logu — docs/DATABASE.md §10.18 (180 gün). Alıcı yalnızca maskeli saklanır.
 *
 * @property int $id
 * @property int|null $message_template_id
 * @property string $template_key
 * @property string $channel
 * @property int|null $user_id
 * @property string|null $recipient_masked
 * @property string $status
 * @property string|null $error
 * @property int $attempts
 */
final class MessageDispatch extends Model
{
    public $timestamps = false;

    protected $fillable = ['message_template_id', 'template_key', 'channel', 'user_id', 'recipient_masked', 'status', 'provider_message_id', 'error', 'attempts', 'queued_at', 'sent_at'];

    protected function casts(): array
    {
        return ['queued_at' => 'immutable_datetime', 'sent_at' => 'immutable_datetime', 'attempts' => 'integer'];
    }
}
