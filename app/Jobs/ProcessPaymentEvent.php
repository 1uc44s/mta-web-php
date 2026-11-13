<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Payments\PaymentProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Webhook olayını asenkron işler — docs/SRS.md FR-PAY-003, ARCHITECTURE §10 (`critical` kuyruğu).
 * Yalnızca olay kimliğini taşır; işlem `PaymentProcessor` içinde kilitli ve idempotenttir (tekrar çalışması güvenli).
 */
final class ProcessPaymentEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $eventId)
    {
        $this->onQueue('critical');
        $this->afterCommit();
    }

    public function handle(PaymentProcessor $processor): void
    {
        $processor->process($this->eventId);
    }
}
