<?php

namespace App\Actions\Admin;

use App\Models\Booking;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ResendBookingEmailAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Booking $booking): void
    {
        DB::transaction(function () use ($booking): void {
            $locked = Booking::query()->with(['user', 'showtime.movie', 'items.seat', 'tickets'])->lockForUpdate()->findOrFail($booking->id);
            $this->audit->log('booking.email_resent', $locked, null, ['reference' => $locked->reference]);
            DB::afterCommit(fn () => Mail::to($locked->user->email)->queue(new \App\Mail\BookingConfirmedMail($locked)));
        });
    }
}
