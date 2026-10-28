<?php

namespace App\Mail;

use App\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LatePaymentRefund extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Refund $refund)
    {
        $this->refund->loadMissing([
            'booking.user',
            'booking.showtime.movie',
            'booking.showtime.screen.cinema',
            'booking.items.ticket',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update about your CineBook payment refund',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.late-payment-refund',
        );
    }
}
