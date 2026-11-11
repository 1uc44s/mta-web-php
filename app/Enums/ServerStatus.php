<?php

declare(strict_types=1);

namespace App\Enums;

/** docs/ARCHITECTURE.md §7.8 */
enum ServerStatus: string
{
    case Online = 'online';
    case Stale = 'stale';
    case Offline = 'offline';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return __('server.status.'.$this->value);
    }

    /** Oyuncu sayısı yalnızca doğrulanmış veya son bilinen (bayat) durumda gösterilir. */
    public function showsPlayers(): bool
    {
        return $this === self::Online || $this === self::Stale;
    }
}
