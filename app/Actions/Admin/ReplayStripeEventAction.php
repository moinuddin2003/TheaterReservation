<?php

namespace App\Actions\Admin;

use App\Enums\RefundStatus;
use App\Jobs\ProcessStripeEventJob;
use App\Models\StripeEvent;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReplayStripeEventAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(StripeEvent $event): void
    {
        DB::transaction(function () use ($event): void {
            $locked = StripeEvent::query()->lockForUpdate()->findOrFail($event->id);
            if ($locked->status !== 'failed') {
                throw ValidationException::withMessages(['event' => 'Only failed Stripe events can be replayed.']);
            }
            $before = $this->audit->snapshot($locked);
            $locked->forceFill(['status' => 'received', 'error_message' => null, 'processed_at' => null])->save();
            $this->audit->log('stripe_event.replay_requested', $locked, $before, $this->audit->snapshot($locked));
            DB::afterCommit(fn () => ProcessStripeEventJob::dispatch($locked->stripe_event_id));
        });
    }
}
