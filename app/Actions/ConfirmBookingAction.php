<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\SeatHold;
use App\Services\AuditLogger;
use App\Services\TokenGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ConfirmBookingAction
{
    public function __construct(
        private AuditLogger $audit,
        private TokenGenerator $tokens,
    ) {}

    public function execute(
        Booking $booking,
        ?string $paymentIntentId = null,
        ?string $checkoutSessionId = null,
    ): Booking {
        return DB::transaction(function () use ($booking, $paymentIntentId, $checkoutSessionId): Booking {
            $booking = Booking::query()->with(['items', 'payments', 'tickets'])->lockForUpdate()->findOrFail($booking->id);
            if ($booking->status === BookingStatus::Confirmed) {
                return $booking;
            }
            if ($booking->status !== BookingStatus::PendingPayment) {
                throw new \DomainException('Only a pending booking can be confirmed.');
            }

            $payment = $booking->payments()->latest('id')->first();
            if (! $payment) {
                throw new \DomainException('A booking cannot be confirmed without a payment record.');
            }
            if ((int) $payment->amount_cents !== (int) $booking->total_cents) {
                throw new \DomainException('The successful payment amount does not match the booking total.');
            }
            $activeHolds = SeatHold::query()
                ->where('booking_id', $booking->id)
                ->where('status', HoldStatus::Active->value)
                ->where('expires_at', '>', now())
                ->count();
            if ($booking->hold_expires_at->isPast() || $activeHolds !== (int) $booking->seats_count) {
                throw new \DomainException('The booking seat hold has expired or is incomplete.');
            }

            $beforeBooking = $this->audit->snapshot($booking);
            $beforePayment = $this->audit->snapshot($payment);
            $payment->forceFill([
                'status' => PaymentStatus::Succeeded,
                'gateway_payment_intent_id' => $paymentIntentId ?? $payment->gateway_payment_intent_id,
                'gateway_session_id' => $checkoutSessionId ?? $payment->gateway_session_id,
                'paid_at' => now(),
            ])->save();
            $this->audit->log('payment.succeeded', $payment, $beforePayment, $this->audit->snapshot($payment));
            $booking->forceFill(['status' => BookingStatus::Confirmed, 'confirmed_at' => now()])->save();
            foreach ($booking->items as $item) {
                if ($item->status !== 'active') {
                    continue;
                }
                $ticket = $item->ticket()->first();
                if (! $ticket) {
                    $booking->tickets()->create([
                        'booking_item_id' => $item->id,
                        'showtime_id' => $booking->showtime_id,
                        'ticket_number' => 'CBT-'.strtoupper(bin2hex(random_bytes(6))),
                        'token' => $this->tokens->generate(),
                        'status' => TicketStatus::Valid,
                    ]);
                }
            }
            $booking->seatHolds()->where('status', HoldStatus::Active->value)->update([
                'status' => HoldStatus::Converted->value,
                'updated_at' => now(),
            ]);
            $this->audit->log('booking.confirmed', $booking, $beforeBooking, $this->audit->snapshot($booking));
            DB::afterCommit(function () use ($booking): void {
                $booking->loadMissing(['user', 'showtime.movie', 'items.ticket']);
                Mail::to($booking->user->email)->queue(new BookingConfirmedMail($booking));
            });

            return $booking->refresh();
        });
    }
}
