<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentNotCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Booking $booking)
    {
        $this->booking->loadMissing([
            'user',
            'showtime.movie',
            'showtime.screen.cinema',
            'items.ticket',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your payment was not completed - seats released',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-not-completed',
        );
    }
}
