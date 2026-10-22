@extends('layouts.cinema')

@section('title', 'Booking confirmed — '.config('cinema.brand'))

@section('content')
    <div class="page-shell booking-page">
        <p class="eyebrow">03 YOUR TICKETS <span aria-hidden="true">/</span> CONFIRMED</p>
        <div class="booking-detail-layout booking-confirmed-layout">
            <section class="booking-panel booking-confirmed-panel">
                <x-booking-status :status="$booking->status" />
                <h1 class="booking-page__title">You’re going to the movies.</h1>
                <p>Your booking is confirmed according to the saved booking record.</p>

                @if ($booking->items->contains(fn ($item) => $item->ticket))
                    <ul class="booking-ticket-list">
                        @foreach ($booking->items as $item)
                            @if ($item->ticket)
                                <li>
                                    <span>{{ $item->seat_label }} · {{ ucfirst($item->seat_type->value) }}</span>
                                    <strong>{{ $item->ticket->ticket_number }}</strong>
                                    <span>{{ ucwords(str_replace('_', ' ', $item->ticket->status->value)) }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                    <button class="button button--primary" type="button" data-print-page>
                        Print or save tickets
                    </button>
                @else
                    <p class="booking-notice">
                        No ticket records are attached to this booking yet. Check booking details for updates.
                    </p>
                @endif

                <a class="button button--quiet" href="{{ route('bookings.show', $booking->reference) }}">
                    Booking details
                </a>
            </section>

            <aside class="booking-detail-side">
                <section class="booking-panel">
                    <x-booking-summary :booking="$booking" />
                </section>
                <a class="text-link" href="{{ route('bookings.index') }}">Manage my bookings →</a>
            </aside>
        </div>
    </div>
@endsection
