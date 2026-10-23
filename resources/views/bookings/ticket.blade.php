@extends('layouts.cinema')

@section('title', 'Ticket '.$ticket->ticket_number)

@section('content')
    @php($movie = $booking->showtime->movie)

    <div class="page-shell booking-page booking-ticket-print">
        <div class="booking-panel booking-ticket-card">
            <p class="eyebrow">YOUR MOVIE TICKET</p>
            <h1 class="booking-page__title">{{ $movie->title }}</h1>

            <div class="booking-ticket-card__details">
                <p><strong>Ticket</strong> {{ $ticket->ticket_number }}</p>
                <p><strong>Seat</strong> {{ $ticket->bookingItem->seat_label }}</p>
                <p><strong>Screen</strong> {{ $booking->showtime->screen->name }}</p>
                <p><strong>Showtime</strong> {{ $booking->showtime->starts_at->format('D, M j, Y · H:i') }}</p>
                <p><strong>Booking reference</strong> {{ $booking->reference }}</p>
                <p><strong>Status</strong> {{ ucwords(str_replace('_', ' ', $ticket->status->value)) }}</p>
            </div>

            <img class="booking-ticket-card__qr" src="{{ $qrCode }}" alt="QR code for ticket {{ $ticket->ticket_number }}">

            <div class="booking-ticket-actions">
                <button class="button button--primary" type="button" data-print-page>
                    Print or save this ticket
                </button>
                <a class="button button--quiet" href="{{ route('bookings.show', $booking->reference) }}">
                    Booking details
                </a>
            </div>
        </div>
    </div>
@endsection
