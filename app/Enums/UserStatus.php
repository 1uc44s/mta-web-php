<?php

declare(strict_types=1);

namespace App\Enums;

/** docs/DATABASE.md §3.1 users.status */
enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case PendingDeletion = 'pending_deletion';
    case Anonymized = 'anonymized';

    /** Giriş yapabilir mi? (pending_deletion iptal için giriş yapabilir) */
    public function canLogin(): bool
    {
        return $this === self::Active || $this === self::PendingDeletion;
    }
}
