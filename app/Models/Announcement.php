<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUlid;
use App\Models\Concerns\Publishable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Duyuru — docs/DATABASE.md §10.15, SRS FR-ADM-011. Kullanıcı başına satır açılmaz; hedef kitle sorgu anında eşleşir.
 *
 * @property int $id
 * @property string $ulid
 * @property string $title
 * @property string $body
 * @property string $priority
 * @property string $audience
 * @property list<string> $channels
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property CarbonImmutable|null $email_dispatched_at
 */
final class Announcement extends Model
{
    use HasPublicUlid;
    use Publishable;

    protected $fillable = [
        'title', 'body', 'priority', 'audience', 'audience_role_ids', 'channels', 'starts_at', 'ends_at',
        'status', 'published_at', 'sort', 'created_by', 'updated_by',
    ];

    protected $attributes = ['priority' => 'info', 'audience' => 'all', 'channels' => '["panel"]'];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'audience_role_ids' => 'array',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'email_dispatched_at' => 'immutable_datetime',
        ];
    }

    /**
     * Şu an gösterilmesi gereken duyurular (yayında + tarih aralığında) ve verilen kanal.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActiveOn(Builder $query, string $channel): Builder
    {
        $now = now();

        return $query->published()
            ->where('starts_at', '<=', $now)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->whereJsonContains('channels', $channel)
            ->orderByRaw("FIELD(priority, 'critical', 'important', 'info')")
            ->latest('starts_at');
    }

    /** @return BelongsToMany<User, $this> */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')->withPivot('read_at');
    }
}
