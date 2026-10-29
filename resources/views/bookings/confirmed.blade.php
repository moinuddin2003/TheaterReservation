@extends('layouts.cinema')

@section('title', 'Booking confirmed')

@section('content')
    <div class="page-shell booking-page">
        <p class="eyebrow">03 YOUR TICKETS <span aria-hidden="true">/</span> CONFIRMED</p>
        <section class="booking-panel booking-confirmed-panel">
            <x-booking-status :status="$booking->status" />
            <h1 class="booking-page__title">You’re going to the movies.</h1>
            <p>
                Your booking for <strong>{{ $booking->showtime->movie->title }}</strong> is confirmed.
                Your tickets are ready below.
            </p>

            @if ($booking->items->contains(fn ($item) => $item->ticket))
                <h2 class="booking-confirmed-panel__tickets-title">Your tickets</h2>
                <ul class="booking-ticket-list">
                    @foreach ($booking->items as $item)
                        @if ($item->ticket)
                            <li>
                                <div>
                                    <span>Seat</span>
                                    <strong>{{ $item->seat_label }} · {{ ucfirst($item->seat_type->value) }}</strong>
                                </div>
                                <div>
                                    <span>Ticket</span>
                                    <strong>{{ $item->ticket->ticket_number }}</strong>
                                </div>
                                <a
                                    class="button button--primary"
                                    href="{{ route('bookings.tickets.show', [$booking->reference, $item->ticket->id]) }}"
                                >View and print ticket <span aria-hidden="true">→</span></a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @else
                <p class="booking-notice">
                    No ticket records are attached to this booking yet. Check booking details for updates.
                </p>
            @endif

            <div class="booking-confirmed-panel__actions">
                <a class="button button--quiet" href="{{ route('bookings.show', $booking->reference) }}">
                    Booking details
                </a>
                <a class="text-link" href="{{ route('bookings.index') }}">Manage my bookings →</a>
            </div>
        </section>
    </div>
@endsection
