<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
        $this->booking->loadMissing(['showtime.movie', 'showtime.screen.cinema']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your CineBook booking has been cancelled');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-cancelled');
    }

    /**
     * @return array<string, mixed>
     */
    public function attachments(): array
    {
        return [];
    }
}
