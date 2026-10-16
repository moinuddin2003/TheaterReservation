<?php

namespace App\Actions\Admin;

use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RetryRefundAction
{
    public function __construct(
        private AuditLogger $audit,
        private ProcessRefundAction $processRefund,
    ) {}

    public function execute(Refund $refund): Refund
    {
        DB::transaction(function () use ($refund): void {
            $locked = Refund::query()->lockForUpdate()->findOrFail($refund->id);
            if ($locked->status !== RefundStatus::Failed) {
                throw ValidationException::withMessages(['refund' => 'Only failed refunds can be retried.']);
            }
            $before = $this->audit->snapshot($locked);
            $locked->forceFill([
                'status' => RefundStatus::Pending,
                'failure_message' => null,
                'processed_at' => null,
                'idempotency_key' => (string) Str::uuid(),
            ])->save();
            $this->audit->log('refund.retry_requested', $locked, $before, $this->audit->snapshot($locked));
        });

        return $this->processRefund->execute($refund->fresh());
    }
}
