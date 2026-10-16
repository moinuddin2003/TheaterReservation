<?php

namespace App\Actions\Admin;

use App\Enums\RefundStatus;
use App\Models\Refund;
use App\Services\AuditLogger;
use App\Services\Payments\PaymentGateway;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ProcessRefundAction
{
    public function __construct(
        private PaymentGateway $gateway,
        private AuditLogger $audit,
    ) {}

    public function execute(Refund $refund): Refund
    {
        $refund = DB::transaction(function () use ($refund): Refund {
            $locked = Refund::query()->with('payment')->lockForUpdate()->findOrFail($refund->id);
            if (! in_array($locked->status, [RefundStatus::Pending, RefundStatus::Failed], true)) {
                throw new \DomainException('Only pending or failed refunds can be processed.');
            }
            $before = $this->audit->snapshot($locked);
            $locked->forceFill(['status' => RefundStatus::Processing, 'failure_message' => null])->save();
            $this->audit->log('refund.processing', $locked, $before, $this->audit->snapshot($locked));

            return $locked;
        });

        try {
            $result = $this->gateway->createRefund(
                $refund->payment,
                (int) $refund->amount_cents,
                $refund->idempotency_key,
            );
            if (! $result->successful) {
                return $this->fail($refund, $result->error ?? 'Stripe refund request failed.');
            }
            if (! $result->id) {
                return $this->fail($refund, 'Stripe returned a successful response without a refund ID.');
            }

            return DB::transaction(function () use ($refund, $result): Refund {
                $locked = Refund::query()->lockForUpdate()->findOrFail($refund->id);
                $before = $this->audit->snapshot($locked);
                $locked->forceFill([
                    'status' => RefundStatus::Succeeded,
                    'gateway_refund_id' => $result->id,
                    'failure_message' => null,
                    'processed_at' => now(),
                ])->save();
                $this->audit->log('refund.succeeded', $locked, $before, $this->audit->snapshot($locked));

                return $locked;
            });
        } catch (Throwable $exception) {
            $this->fail($refund, mb_substr($exception->getMessage(), 0, 255));

            throw $exception;
        }
    }

    private function fail(Refund $refund, string $message): Refund
    {
        return DB::transaction(function () use ($refund, $message): Refund {
            $locked = Refund::query()->lockForUpdate()->findOrFail($refund->id);
            $before = $this->audit->snapshot($locked);
            $locked->forceFill([
                'status' => RefundStatus::Failed,
                'failure_message' => mb_substr($message, 0, 255),
                'processed_at' => now(),
            ])->save();
            $this->audit->log('refund.failed', $locked, $before, $this->audit->snapshot($locked));

            return $locked;
        });
    }
}
