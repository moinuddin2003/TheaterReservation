<?php

namespace App\Jobs;

use App\Services\Payments\PaymentGateway;
use App\Services\StripeEventProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessStripeEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $eventId) {}

    public function handle(PaymentGateway $gateway, StripeEventProcessor $processor): void
    {
        $processor->process($gateway->retrieveEvent($this->eventId));
    }
}
