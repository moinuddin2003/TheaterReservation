@extends('layouts.cinema')

@section('title', 'Payment status')

@section('content')
    @php($latestPayment = $booking->payments->sortByDesc('created_at')->first())

    <div class="page-shell booking-page">
        <p class="eyebrow">BOOKING <span aria-hidden="true">/</span> PAYMENT STATUS</p>
        <div class="booking-detail-layout booking-status-layout">
            <section class="booking-panel booking-status-panel">
                <x-booking-status :status="$booking->status" />
                <h1 class="booking-page__title">
                    @if ($booking->isPendingPayment())
                        Payment processing
                    @elseif ($booking->isFailed())
                        Payment was not completed
                    @elseif ($booking->isExpired())
                        Booking expired
                    @else
                        Booking status
                    @endif
                </h1>
                <p>
                    @if ($booking->isPendingPayment())
                        @if ($latestPayment)
                            The saved booking is still waiting for a verified payment result.
                        @else
                            No payment was started for this booking. Your seats are reserved until the hold expires.
                        @endif
                    @else
                        This is the latest status currently saved for your booking.
                    @endif
                </p>
                @if ($latestPayment)
                    <p>
                        Payment status:
                        {{ ucwords(str_replace('_', ' ', $latestPayment->status->value)) }}
                    </p>
                @else
                    <p>No payment record is attached to this booking.</p>
                @endif
                <p class="booking-notice">
                    This page only displays saved status. It does not contact Stripe or start another payment.
                </p>
                @if ($canRetryCheckout)
                    <a class="button button--primary" href="{{ route('bookings.checkout', $booking->reference) }}">
                        Continue to checkout
                    </a>
                @elseif ($booking->isPendingPayment())
                    <p class="booking-notice">
                        Your seat hold has expired. Choose seats again to start a new booking and payment.
                    </p>
                @endif
                <div class="booking-list-card__actions">
                    <a class="button button--quiet" href="{{ route('bookings.processing', $booking->reference) }}">
                        Refresh status
                    </a>
                    <a class="button button--quiet" href="{{ route('bookings.index') }}">My bookings</a>
                    <a class="button button--quiet" href="{{ route('bookings.show', $booking->reference) }}">
                        Booking details
                    </a>
                </div>
            </section>

            <aside class="booking-detail-side">
                <section class="booking-panel">
                    <x-booking-summary :booking="$booking" />
                </section>
            </aside>
        </div>
    </div>
@endsection
