<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Talebe yeni public mesaj — docs/ARCHITECTURE.md §9, ROUTES §7.4. Yalnızca kimlik alanları; gövde asla yayınlanmaz.
 */
final class SupportMessagePosted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(public readonly string $ticketUlid, public readonly string $authorType) {}

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('support.ticket.'.$this->ticketUlid)];
    }

    public function broadcastAs(): string
    {
        return 'support.message';
    }

    /** @return array<string, string> */
    public function broadcastWith(): array
    {
        return ['ticket' => $this->ticketUlid, 'author' => $this->authorType];
    }
}
