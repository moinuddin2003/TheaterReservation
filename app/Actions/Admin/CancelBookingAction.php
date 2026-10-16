<?php

namespace App\Actions\Admin;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Mail\BookingCancelledMail;
use App\Models\Booking;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CancelBookingAction
{
    public function __construct(
        private AuditLogger $audit,
        private IssueRefundAction $issueRefund,
    ) {}

    public function execute(Booking $booking, string $reason, bool $override = false, ?User $actor = null): Booking
    {
        return DB::transaction(function () use ($booking, $reason, $override, $actor): Booking {
            $booking = Booking::query()->with(['showtime', 'items', 'payments'])->lockForUpdate()->findOrFail($booking->id);
            if (! in_array($booking->status, [BookingStatus::PendingPayment, BookingStatus::Confirmed], true)) {
                throw ValidationException::withMessages(['booking' => 'Only pending or confirmed bookings can be cancelled.']);
            }
            $adminOverride = $override && ($actor?->role?->value ?? auth()->user()?->role?->value) === 'admin';
            $salesDeadline = $booking->showtime->starts_at->subHours(2);
            if (now()->utc()->greaterThan($salesDeadline) && ! $adminOverride) {
                throw ValidationException::withMessages(['booking' => 'Customer cancellations close 2 hours before showtime.']);
            }
            if ($override && ! $adminOverride) {
                throw ValidationException::withMessages(['override' => 'Only an administrator can override the cancellation deadline.']);
            }
            if ($adminOverride && trim($reason) === '') {
                throw ValidationException::withMessages(['reason' => 'An administrator override requires a reason.']);
            }

            $before = $this->audit->snapshot($booking);
            $booking->forceFill([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor?->id ?? auth()->id(),
                'cancel_reason' => mb_substr($reason, 0, 255),
            ])->save();
            $booking->seatHolds()->where('status', HoldStatus::Active->value)->update([
                'status' => HoldStatus::Released->value,
                'released_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($booking->items()->where('status', 'active')->get() as $item) {
                $itemBefore = $this->audit->snapshot($item);
                $item->forceFill(['status' => 'cancelled'])->save();
                $this->audit->log('booking_item.cancelled', $item, $itemBefore, $this->audit->snapshot($item));
            }
            foreach ($booking->tickets()->where('status', '!=', TicketStatus::Voided->value)->get() as $ticket) {
                $ticketBefore = $this->audit->snapshot($ticket);
                $ticket->forceFill(['status' => TicketStatus::Voided, 'voided_at' => now()])->save();
                $this->audit->log('ticket.voided', $ticket, $ticketBefore, $this->audit->snapshot($ticket));
            }
            $this->audit->log('booking.cancelled', $booking, $before, $this->audit->snapshot($booking));
            DB::afterCommit(function () use ($booking): void {
                $booking->loadMissing(['user', 'showtime.movie']);
                Mail::to($booking->user->email)->queue(new BookingCancelledMail($booking));
            });

            $payment = $booking->payments()->where('status', PaymentStatus::Succeeded->value)->latest('id')->first();
            if ($payment) {
                $alreadyReserved = (int) $payment->refunds()
                    ->whereIn('status', ['pending', 'processing', 'succeeded'])
                    ->sum('amount_cents');
                $refundable = max(0, (int) $payment->amount_cents - $alreadyReserved);
                if ($refundable > 0) {
                    $this->issueRefund->execute(
                        $booking,
                        $refundable,
                        $reason ?: 'booking_cancelled',
                        $actor,
                    );
                }
            }

            return $booking->refresh();
        });
    }
}
