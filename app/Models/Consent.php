<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** docs/DATABASE.md §3.10 — rıza/onay geçmişi (append-only; son kayıt geçerlidir). */
final class Consent extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = ['terms', 'kvkk_ack', 'cookies_analytics', 'marketing_email', 'whatsapp_optin', 'rules_ack'];

    protected $fillable = ['user_id', 'anon_id', 'type', 'version', 'granted', 'ip_masked'];

    protected function casts(): array
    {
        return ['granted' => 'boolean', 'created_at' => 'immutable_datetime'];
    }
}
