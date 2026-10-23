@extends('layouts.cinema')

@section('title', 'Booking details')

@section('content')
    @php($latestPayment = $booking->payments->sortByDesc('created_at')->first())
    @php($latestRefund = $booking->refunds->sortByDesc('created_at')->first())

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
                <h1 class="booking-page__title">Your booking, all in one place</h1>
                <x-booking-status :status="$booking->status" />
            </div>
            <p>Reserved {{ $booking->created_at->copy()->timezone(config('cinema.timezone'))->format('M j, Y \a\t H:i') }}</p>
        </div>

        <div class="booking-detail-layout">
            <div class="booking-detail-main">
                <section class="booking-panel">
                    <x-booking-summary :booking="$booking" />
                </section>

                <section class="booking-panel">
                    <h2>Your seats and tickets</h2>
                    @if ($booking->items->isNotEmpty())
                        <div class="booking-table-wrap">
                            <table class="booking-table">
                                <thead>
                                    <tr>
                                        <th>Seat</th>
                                        <th>Ticket</th>
                                        <th>Ticket status</th>
                                        <th>Price</th>
                                        <th>Ticket page</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($booking->items as $item)
                                        <tr>
                                            <td>{{ $item->seat_label }} · {{ ucfirst($item->seat_type->value) }}</td>
                                            <td>{{ $item->ticket?->ticket_number ?? 'Not issued' }}</td>
                                            <td>
                                                {{ $item->ticket
                                                    ? ucwords(str_replace('_', ' ', $item->ticket->status->value))
                                                    : 'Awaiting confirmation' }}
                                            </td>
                                            <td>{{ number_format($item->price_cents / 100, 2) }} {{ strtoupper($booking->currency) }}</td>
                                            <td>
                                                @if ($item->ticket && $booking->isConfirmed() && ! $item->ticket->isVoid())
                                                    <a href="{{ route('bookings.tickets.show', [$booking->reference, $item->ticket->id]) }}">
                                                        View ticket
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="booking-muted">Seat details have not been recorded for this booking.</p>
                    @endif
                    <p class="booking-muted">
                        Ticket access is based on the saved ticket records for this booking.
                    </p>
                </section>

                <section class="booking-panel">
                    <h2>Payment</h2>
                    @if ($latestPayment)
                        <p>
                            {{ ucwords(str_replace('_', ' ', $latestPayment->status->value)) }}
                            <span aria-hidden="true">·</span>
                            {{ strtoupper($latestPayment->currency) }}
                            {{ number_format($latestPayment->amount_cents / 100, 2) }}
                        </p>
                        <p>Provider: {{ strtoupper($latestPayment->gateway) }}</p>
                        @if ($latestPayment->paid_at)
                            <p>Paid {{ $latestPayment->paid_at->copy()->timezone(config('cinema.timezone'))->format('M j, Y \a\t H:i') }}</p>
                        @endif
                    @else
                        <p class="booking-muted">No payment record is attached to this booking.</p>
                    @endif

                    @forelse ($booking->refunds as $refund)
                        <p>
                            Refund {{ ucwords(str_replace('_', ' ', $refund->status->value)) }}
                            <span aria-hidden="true">·</span>
                            {{ number_format($refund->amount_cents / 100, 2) }}
                            {{ strtoupper($refund->currency) }}
                        </p>
                    @empty
                        <p class="booking-muted">No refund record.</p>
                    @endforelse
                </section>
            </div>

            <aside class="booking-detail-side">
                <section class="booking-panel">
                    <h2>Booking timeline</h2>
                    <ul class="booking-timeline">
                        <li>Booking created · {{ $booking->created_at->copy()->timezone(config('cinema.timezone'))->format('M j, H:i') }}</li>
                        @if ($latestPayment)
                            <li>
                                Payment {{ ucwords(str_replace('_', ' ', $latestPayment->status->value)) }}
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
                        <a
                            class="button button--quiet"
                            href="{{ route('bookings.processing', $booking->reference) }}"
                        >Check payment status <span aria-hidden="true">→</span></a>
                    @else
                        <p class="booking-muted">This booking has no further customer action available here.</p>
                    @endif
                    <p class="booking-muted">Saved payment and refund records determine the actions shown here.</p>
                </section>
            </aside>
        </div>
    </div>
@endsection
