<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * BIGINT PK + dışa açık `ulid` kolonu (docs/PRD.md D-09). URL'lerde ulid kullanılır.
 *
 * @mixin Model
 */
trait HasPublicUlid
{
    use HasUlids;

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
