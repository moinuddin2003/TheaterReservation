@extends('layouts.cinema')

@section('title', 'Seat hold — '.config('cinema.brand'))

@section('content')
    <div class="page-shell booking-page">
        <p class="booking-steps">
            <strong>01 SELECT SEATS</strong>
            <span aria-hidden="true">→</span> 02 CHECKOUT
            <span aria-hidden="true">→</span> 03 YOUR TICKETS
        </p>
        <h1 class="booking-page__title">Your seats are on hold</h1>

        <div class="booking-detail-layout">
            <div class="booking-detail-main">
                <section class="booking-panel">
                    <x-booking-summary :booking="$booking" />
                </section>

                <section class="booking-panel">
                    <h2>Order details</h2>
                    <div class="booking-table-wrap">
                        <table class="booking-table">
                            <thead>
                                <tr>
                                    <th>Seat</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($booking->items as $item)
                                    <tr>
                                        <td>{{ $item->seat_label }}</td>
                                        <td>{{ ucfirst($item->seat_type->value) }}</td>
                                        <td>{{ number_format($item->price_cents / 100, 2) }} {{ strtoupper($booking->currency) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="booking-muted">
                        {{ $booking->items->count() }} seats
                        <span aria-hidden="true">·</span>
                        Total {{ strtoupper($booking->currency) }}
                        {{ number_format($booking->total_cents / 100, 2) }}
                    </p>
                </section>
            </div>

            <aside class="booking-detail-side">
                <section class="booking-panel booking-hold-panel">
                    <p class="eyebrow">HOLD ACTIVE</p>
                    <p class="booking-countdown" data-hold-countdown
                        data-hold-expires-at="{{ $booking->hold_expires_at->toIso8601String() }}">
                        {{ $booking->hold_expires_at->diffForHumans() }}
                    </p>
                    <p>
                        Hold expires {{ $booking->hold_expires_at->format('M j, Y \a\t H:i') }}.
                    </p>
                    <p class="booking-notice">
                        Checkout is not connected yet. This page will not charge you or change the booking.
                    </p>
                    <a class="button button--quiet" href="{{ route('bookings.show', $booking->reference) }}">
                        Booking details
                    </a>
                </section>
            </aside>
        </div>
    </div>
@endsection
