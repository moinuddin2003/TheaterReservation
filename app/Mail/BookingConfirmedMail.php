<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
        $this->booking->loadMissing(['showtime.movie', 'showtime.screen.cinema', 'items']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your CineBook booking is confirmed');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-confirmed');
    }

    /**
     * @return array<string, mixed>
     */
    public function attachments(): array
    {
        return [];
    }
}
