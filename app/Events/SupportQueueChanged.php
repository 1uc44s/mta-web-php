<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Branş destek kuyruğu değişti — ACP sayaçları yenilenir (docs/ARCHITECTURE.md §9). Yük yalnızca branş kimliği.
 */
final class SupportQueueChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(public readonly int $branchId) {}

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('staff.branch.'.$this->branchId)];
    }

    public function broadcastAs(): string
    {
        return 'support.queue';
    }

    /** @return array<string, int> */
    public function broadcastWith(): array
    {
        return ['branch' => $this->branchId];
    }
}
