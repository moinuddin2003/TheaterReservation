<?php

namespace App\Jobs;

use App\Actions\Admin\ProcessRefundAction;
use App\Models\Refund;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessRefundJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $refundId) {}

    public function handle(ProcessRefundAction $processRefund): void
    {
        $processRefund->execute(Refund::query()->findOrFail($this->refundId));
    }
}
