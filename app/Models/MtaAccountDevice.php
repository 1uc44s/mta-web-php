<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cihaz ilişkilendirmesi — docs/DATABASE.md §5.5, ARCHITECTURE §7.6. Ham serial saklanmaz; yalnızca
 * `HMAC(serial, BRIDGE_SERIAL_PEPPER)` (OD-07). `serial_hmac` hiçbir arayüze çıkmaz.
 *
 * @property int $id
 * @property int $mta_account_id
 * @property string $serial_hmac
 * @property string|null $label
 * @property string $status
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $revoked_at
 * @property CarbonImmutable|null $created_at
 */
final class MtaAccountDevice extends Model
{
    protected $fillable = ['mta_account_id', 'serial_hmac', 'label', 'status', 'link_code_id', 'approved_by', 'approved_at', 'revoked_at'];

    protected $hidden = ['serial_hmac'];

    protected function casts(): array
    {
        return ['approved_at' => 'immutable_datetime', 'revoked_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<MtaAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MtaAccount::class, 'mta_account_id');
    }

    /** Arayüzde gösterilen kısa parmak izi (HMAC'in ilk 8 karakteri; serial'den geri türetilemez). */
    public function fingerprint(): string
    {
        return strtoupper(substr($this->serial_hmac, 0, 4).'-'.substr($this->serial_hmac, 4, 4));
    }
}
