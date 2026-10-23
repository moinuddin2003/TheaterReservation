@extends('layouts.cinema')

@section('title', 'Cancel booking')

@section('content')
    @php($latestRefund = $booking->refunds->sortByDesc('created_at')->first())

    <div class="page-shell booking-page">
        <p class="eyebrow">
            <a href="{{ route('bookings.show', $booking->reference) }}">BOOKING DETAILS</a>
            <span aria-hidden="true">/</span> CANCEL
        </p>

        <div class="booking-detail-layout booking-confirmed-layout">
            <section class="booking-panel booking-confirmed-panel">
                <h1 class="booking-page__title">Cancel this booking?</h1>
                <x-booking-summary :booking="$booking" />

                <p>
                    Cancellation releases your seats and makes the tickets invalid.
                    If the booking is eligible, the remaining amount will be refunded
                    to the original Stripe payment method.
                </p>

                @if ($errors->has('booking'))
                    <p class="booking-notice" role="alert">{{ $errors->first('booking') }}</p>
                @endif

                @if ($latestRefund)
                    <p>
                        Latest refund:
                        {{ ucwords(str_replace('_', ' ', $latestRefund->status->value)) }}
                        · {{ number_format($latestRefund->amount_cents / 100, 2) }}
                        {{ strtoupper($latestRefund->currency) }}
                    </p>
                @endif

                @if ($booking->isConfirmed() || $latestRefund?->isFailed())
                    <form method="POST" action="{{ route('bookings.cancel.request', $booking->reference) }}">
                        @csrf
                        <button class="button button--primary" type="submit">
                            {{ $latestRefund?->isFailed() ? 'Try refund again' : 'Confirm cancellation' }}
                        </button>
                    </form>
                @else
                    <p class="booking-notice">
                        This booking cannot be cancelled again. Check its saved refund status in booking details.
                    </p>
                @endif

                <a class="button button--quiet" href="{{ route('bookings.show', $booking->reference) }}">
                    Keep my booking
                </a>
            </section>
        </div>
    </div>
@endsection
