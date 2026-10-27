@extends('layouts.cinema')

@section('title', 'Booking details')

@section('content')
    @php($latestPayment = $booking->payments->sortByDesc('created_at')->first())
    @php($latestRefund = $booking->refunds->sortByDesc('created_at')->first())
    @php($activeSeatHolds = $booking->seatHolds->filter(fn ($hold) => $hold->isActive() && $hold->expires_at->isFuture()))
    @php($canContinuePayment = $booking->isPendingPayment() && $activeSeatHolds->count() === $booking->seats_count)

    <div class="page-shell booking-page">
        @if (session('status'))
            <p class="booking-notice" role="status">{{ session('status') }}</p>
        @endif

        <p class="eyebrow">
            <a href="{{ route('bookings.index') }}">MY BOOKINGS</a>
            <span aria-hidden="true">/</span> {{ $booking->reference }}
        </p>
        <div class="booking-detail-heading">
            <div>
                <h1 class="booking-page__title">Booking details</h1>
                <x-booking-status :status="$booking->status" />
            </div>
            <p>Created {{ $booking->created_at->copy()->timezone(config('cinema.timezone'))->format('M j, Y \a\t H:i') }}</p>
        </div>

        @if ($booking->isPendingPayment())
            <p class="booking-notice" role="status">
                @if ($canContinuePayment)
                    Your seats are being held for you until
                    {{ $booking->hold_expires_at->copy()->timezone(config('cinema.timezone'))->format('g:i A') }}.
                    Complete payment before then to confirm your booking.
                @else
                    Your seat hold has expired. These seats are no longer reserved. Choose seats again to make a new booking.
                @endif
            </p>
        @elseif ($booking->isFailed() || $booking->isExpired())
            <p class="booking-notice" role="status">
                This booking is no longer active and its seats are not reserved. Choose seats again to make a new booking.
            </p>
        @elseif ($booking->isRefunded())
            <p class="booking-notice" role="status">
                This booking was refunded. Your bank may take several business days to show the money.
            </p>
        @endif

        <div class="booking-detail-layout">
            <div class="booking-detail-main">
                <section class="booking-panel">
                    <x-booking-summary :booking="$booking" />
                </section>

                <section class="booking-panel">
                    <h2>{{ $booking->isConfirmed() ? 'Your seats and tickets' : 'Seats in this booking' }}</h2>
                    @if ($booking->items->isNotEmpty())
                        <div class="booking-table-wrap">
                            <table class="booking-table">
                                <thead>
                                    <tr>
                                        <th>Seat</th>
                                        @if ($booking->isConfirmed())
                                            <th>Ticket</th>
                                            <th>Ticket status</th>
                                            <th>Price</th>
                                            <th>Ticket page</th>
                                        @else
                                            <th>Price</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($booking->items as $item)
                                        <tr>
                                            <td>{{ $item->seat_label }} · {{ ucfirst($item->seat_type->value) }}</td>
                                            @if ($booking->isConfirmed())
                                                <td>{{ $item->ticket?->ticket_number ?? 'Not issued' }}</td>
                                                <td>
                                                    {{ $item->ticket
                                                        ? ucwords(str_replace('_', ' ', $item->ticket->status->value))
                                                        : 'Awaiting confirmation' }}
                                                </td>
                                                <td>{{ number_format($item->price_cents / 100, 2) }} {{ strtoupper($booking->currency) }}</td>
                                                <td>
                                                    @if ($item->ticket && ! $item->ticket->isVoid())
                                                        <a href="{{ route('bookings.tickets.show', [$booking->reference, $item->ticket->id]) }}">
                                                            View ticket
                                                        </a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            @else
                                                <td>{{ number_format($item->price_cents / 100, 2) }} {{ strtoupper($booking->currency) }}</td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @elseif ($activeSeatHolds->isNotEmpty())
                        <ul>
                            @foreach ($activeSeatHolds as $hold)
                                <li>
                                    Seat {{ $hold->seat->label }} · {{ ucfirst($hold->seat->type->value) }}
                                    · {{ number_format($hold->price_cents / 100, 2) }} {{ strtoupper($booking->currency) }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="booking-muted">Seat details are not available because these seats are no longer held.</p>
                    @endif
                    @if ($booking->isConfirmed())
                        <p class="booking-muted">Your tickets are ready to view and print.</p>
                    @elseif ($canContinuePayment)
                        <p class="booking-muted">
                            These seats are reserved temporarily. They will be released if you do not finish payment before the hold expires.
                        </p>
                    @endif
                </section>

                <section class="booking-panel">
                    <h2>Payment summary</h2>
                    @if ($latestPayment)
                        <p>
                            Amount: {{ strtoupper($latestPayment->currency) }}
                            {{ number_format($latestPayment->amount_cents / 100, 2) }}
                        </p>
                        @if ($latestPayment->isSucceeded() || $latestPayment->isRefunded())
                            <p>Payment received {{ $latestPayment->paid_at?->copy()->timezone(config('cinema.timezone'))->format('M j, Y \a\t H:i') }}</p>
                        @elseif ($latestPayment->isPending())
                            <p>Payment has not been completed yet.</p>
                        @elseif ($latestPayment->isFailed())
                            <p>Payment was not completed.</p>
                        @elseif ($latestPayment->isExpired())
                            <p>No payment was completed before the seat hold expired.</p>
                        @endif
                    @else
                        <p class="booking-muted">You have not started payment for this booking yet.</p>
                    @endif

                    @if ($latestRefund)
                        <p>
                            Refund:
                            {{ $latestRefund->isSucceeded() ? 'sent' : ($latestRefund->isFailed() ? 'could not be completed' : 'being processed') }}
                            · {{ strtoupper($latestRefund->currency) }}
                            {{ number_format($latestRefund->amount_cents / 100, 2) }}
                        </p>
                    @endif
                </section>
            </div>

            <aside class="booking-detail-side">
                <section class="booking-panel">
                    <h2>Booking timeline</h2>
                    <ul class="booking-timeline">
                        <li>Booking created · {{ $booking->created_at->copy()->timezone(config('cinema.timezone'))->format('M j, H:i') }}</li>
                        @if ($latestPayment)
                            <li>
                                @if ($latestPayment->isSucceeded() || $latestPayment->isRefunded())
                                    Payment received
                                @elseif ($latestPayment->isPending())
                                    Payment in progress
                                @elseif ($latestPayment->isFailed())
                                    Payment failed
                                @else
                                    Payment window expired
                                @endif
                                · {{ ($latestPayment->paid_at ?? $latestPayment->created_at)->copy()->timezone(config('cinema.timezone'))->format('M j, H:i') }}
                            </li>
                        @endif
                        @if ($booking->confirmed_at)
                            <li>Booking confirmed · {{ $booking->confirmed_at->copy()->timezone(config('cinema.timezone'))->format('M j, H:i') }}</li>
                        @endif
                        @if ($booking->cancelled_at)
                            <li>Booking cancelled · {{ $booking->cancelled_at->copy()->timezone(config('cinema.timezone'))->format('M j, H:i') }}</li>
                        @endif
                    </ul>
                </section>

                <section class="booking-panel">
                    <h2>Next step</h2>
                    @if ($booking->isConfirmed())
                        <a
                            class="button button--primary"
                            href="{{ route('bookings.confirmed', $booking->reference) }}"
                        >View tickets <span aria-hidden="true">→</span></a>
                        <a
                            class="button button--quiet"
                            href="{{ route('bookings.cancel', $booking->reference) }}"
                        >Cancel booking</a>
                    @elseif ($booking->isCancelled() && $latestRefund?->isFailed())
                        <a
                            class="button button--primary"
                            href="{{ route('bookings.cancel', $booking->reference) }}"
                        >Try refund again</a>
                    @elseif ($booking->isPendingPayment())
                        @if ($canContinuePayment)
                            <a
                                class="button button--primary"
                                href="{{ route('bookings.checkout', $booking->reference) }}"
                            >Continue to payment <span aria-hidden="true">→</span></a>
                            <p class="booking-muted">Finish payment to confirm your seats.</p>
                        @else
                            <a
                                class="button button--primary"
                                href="{{ route('showtimes.show', $booking->showtime) }}"
                            >Choose seats again <span aria-hidden="true">→</span></a>
                        @endif
                    @else
                        @if ($booking->isFailed() || $booking->isExpired())
                            <a
                                class="button button--primary"
                                href="{{ route('showtimes.show', $booking->showtime) }}"
                            >Choose seats again <span aria-hidden="true">→</span></a>
                        @else
                            <p class="booking-muted">There is no further action for this booking.</p>
                        @endif
                    @endif
                </section>
            </aside>
        </div>
    </div>
@endsection
