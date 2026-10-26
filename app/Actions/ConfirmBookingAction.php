<?php

namespace App\Actions;

use App\Enums\BookingConfirmationResult;
use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\SeatHold;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class ConfirmBookingAction
{
    public function execute(Payment $payment, string $paymentIntentId): BookingConfirmationResult
    {
        return DB::transaction(function () use ($payment, $paymentIntentId): BookingConfirmationResult {
            $payment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            $booking = Booking::query()->whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();

            if ($booking->isConfirmed()) {
                return BookingConfirmationResult::AlreadyConfirmed;
            }

            if (! $booking->isPendingPayment() || ! $booking->hold_expires_at->isFuture()) {
                return BookingConfirmationResult::CannotConfirm;
            }

            $holds = SeatHold::query()
                ->where('booking_id', $booking->getKey())
                ->where('status', HoldStatus::Active)
                ->where('expires_at', '>', now())
                ->with('seat')
                ->orderBy('seat_id')
                ->lockForUpdate()
                ->get();

            if ($holds->count() !== $booking->seats_count) {
                return BookingConfirmationResult::CannotConfirm;
            }

            $payment->forceFill([
                'gateway_payment_intent_id' => $paymentIntentId,
                'status' => PaymentStatus::Succeeded,
                'paid_at' => now(),
                'failure_reason' => null,
            ])->save();

            foreach ($holds as $index => $hold) {
                $item = new BookingItem([
                    'showtime_id' => $booking->showtime_id,
                    'seat_id' => $hold->seat_id,
                    'seat_label' => $hold->seat->label,
                    'seat_type' => $hold->seat->type,
                ]);
                $item->forceFill(['price_cents' => $hold->price_cents]);
                $booking->items()->save($item);

                $ticket = new Ticket([
                    'booking_item_id' => $item->getKey(),
                    'showtime_id' => $booking->showtime_id,
                    'ticket_number' => 'T-'.$booking->getKey().'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                ]);
                $ticket->forceFill(['status' => TicketStatus::Valid]);
                $booking->tickets()->save($ticket);
            }

            $holds->each(fn (SeatHold $hold) => $hold->forceFill([
                'status' => HoldStatus::Converted,
                'released_at' => now(),
            ])->save());

            $booking->forceFill([
                'status' => BookingStatus::Confirmed,
                'confirmed_at' => now(),
            ])->save();

            return BookingConfirmationResult::Confirmed;
        });
    }
}
