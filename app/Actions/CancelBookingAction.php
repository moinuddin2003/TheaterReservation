<?php

namespace App\Actions;

use App\Actions\Admin\CancelBookingAction as AdminCancelBookingAction;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CancelBookingAction
{
    public function __construct(private AdminCancelBookingAction $cancelBooking) {}

    public function execute(Booking $booking, User $customer, string $reason = 'Customer cancellation'): Booking
    {
        if ((int) $booking->user_id !== (int) $customer->id) {
            throw ValidationException::withMessages(['booking' => 'You may only cancel your own booking.']);
        }

        return $this->cancelBooking->execute($booking, $reason, actor: $customer);
    }
}
