<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\Push\PushSubscriptions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Web Push gönderimi kuyrukta — istek süresine push servisinin gecikmesi eklenmez. Mesaj yalnızca gösterim alanlarıdır.
 */
final class SendWebPush implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    /** @param  array{title: string, body: string|null, url: string, tag: string}  $message */
    public function __construct(public readonly int $userId, public readonly array $message)
    {
        $this->afterCommit();
    }

    public function handle(PushSubscriptions $push): void
    {
        $user = User::query()->find($this->userId);
        if ($user !== null) {
            $push->sendToUser($user, $this->message);
        }
    }
}
