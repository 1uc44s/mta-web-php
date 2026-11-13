<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
 * Özel yayın kanalları — docs/ROUTES.md §7.4, ARCHITECTURE §9. Yetki: `POST /broadcasting/auth` (web oturumu).
 * Askıdaki/silinmiş hesaplar kanal alamaz.
 */

Broadcast::channel('user.{id}', fn (User $user, string $id): bool => $user->status === UserStatus::Active && $user->id === (int) $id);

Broadcast::channel('support.ticket.{ulid}', function (User $user, string $ulid): bool {
    $ticket = SupportTicket::query()->where('ulid', $ulid)->first();
    if ($ticket === null || $user->status !== UserStatus::Active) {
        return false;
    }

    return $ticket->user_id === $user->id
        || ($user->can('support.read') && SupportTicket::query()->whereKey($ticket->id)->visibleTo($user)->exists());
});

Broadcast::channel('staff.branch.{id}', fn (User $user, string $id): bool => $user->status === UserStatus::Active
    && $user->can('support.read')
    && ($user->can('support.all_branches') || in_array((int) $id, $user->activeBranchIds(), true)));
