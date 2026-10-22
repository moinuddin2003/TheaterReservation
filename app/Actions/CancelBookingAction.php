<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\SeatHold;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelBookingAction
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function execute(
        Booking $booking,
        User $actor,
        string $reason,
        ?string $overrideReason = null,
        ?string $ipAddress = null,
        ?string $note = null,
    ): Booking {
        return DB::transaction(function () use ($booking, $actor, $reason, $overrideReason, $ipAddress, $note): Booking {
            $booking = Booking::query()
                ->with('showtime')
                ->lockForUpdate()
                ->findOrFail($booking->id);

            if (! $booking->isConfirmed()) {
                throw ValidationException::withMessages([
                    'cancel_reason' => 'Only confirmed bookings can be cancelled.',
                ]);
            }

            if (blank($reason)) {
                throw ValidationException::withMessages([
                    'cancel_reason' => 'A cancellation reason is required.',
                ]);
            }

            if (! $booking->showtime->starts_at->isFuture()) {
                throw ValidationException::withMessages([
                    'cancel_reason' => 'A booking cannot be cancelled after its showtime has started.',
                ]);
            }

            if ($booking->tickets()->where('status', TicketStatus::Used)->exists()) {
                throw ValidationException::withMessages([
                    'cancel_reason' => 'This booking has an admitted ticket and cannot be cancelled.',
                ]);
            }

            $cancelWindowHours = Setting::query()
                ->whereKey('cancel_window_hours')
                ->value('value');
            $cancelWindowHours = $cancelWindowHours === null
                ? (int) config('cinebook.cancel_window_hours', 2)
                : (int) $cancelWindowHours;
            $cutoff = $booking->showtime->starts_at->copy()->subHours(max(0, $cancelWindowHours));

            if (now()->greaterThan($cutoff) && blank($overrideReason)) {
                throw ValidationException::withMessages([
                    'override_reason' => 'The cancellation window has closed. Provide a reason for the admin override.',
                ]);
            }

            $before = [
                'status' => $booking->status->value,
                'cancelled_at' => $booking->cancelled_at?->toISOString(),
            ];
            $now = now();

            $booking->forceFill([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => $now,
                'cancelled_by' => $actor->id,
                'cancel_reason' => trim($reason),
            ])->save();

            BookingItem::query()
                ->where('booking_id', $booking->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled', 'updated_at' => $now]);

            Ticket::query()
                ->where('booking_id', $booking->id)
                ->where('status', TicketStatus::Valid)
                ->update([
                    'status' => TicketStatus::Void->value,
                    'voided_at' => $now,
                    'updated_at' => $now,
                ]);

            SeatHold::query()
                ->where('booking_id', $booking->id)
                ->where('status', HoldStatus::Active)
                ->update([
                    'status' => HoldStatus::Released->value,
                    'released_at' => $now,
                    'updated_at' => $now,
                ]);

            Payment::query()
                ->where('booking_id', $booking->id)
                ->where('status', PaymentStatus::Pending)
                ->update([
                    'status' => PaymentStatus::Expired->value,
                    'failure_reason' => 'Booking cancelled by an administrator.',
                    'updated_at' => $now,
                ]);

            $this->auditLogger->log(
                $actor,
                'booking.cancelled',
                $booking,
                $before,
                [
                    'status' => BookingStatus::Cancelled->value,
                    'cancel_reason' => $booking->cancel_reason,
                    'override_reason' => filled($overrideReason) ? trim($overrideReason) : null,
                    'note' => filled($note) ? trim($note) : null,
                ],
                $ipAddress,
            );

            return $booking->fresh(['user', 'showtime.movie', 'showtime.screen.cinema', 'items.ticket', 'payments']);
        });
    }
}
