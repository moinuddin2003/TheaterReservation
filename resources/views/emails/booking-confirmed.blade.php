<h1>Your booking is confirmed</h1>

<p>Hello {{ $booking->user->name }},</p>

<p>Your payment has been received and your cinema booking is ready.</p>

<h2>{{ $booking->showtime->movie->title }}</h2>
<p>
    Showtime:
    {{ $booking->showtime->starts_at->copy()->timezone(config('cinema.timezone'))->format('M j, Y \a\t g:i A') }}
    <br>
    {{ $booking->showtime->screen->cinema->name }} · {{ $booking->showtime->screen->name }}
</p>
<p>Booking reference: <strong>{{ $booking->reference }}</strong></p>

<h3>Your seats and tickets</h3>
<ul>
    @foreach ($booking->items as $item)
        <li>
            Seat {{ $item->seat_label }}
            @if ($item->ticket)
                - Ticket {{ $item->ticket->ticket_number }}
            @endif
        </li>
    @endforeach
</ul>

<p>
    Total paid:
    {{ strtoupper($booking->currency) }} {{ number_format($booking->total_cents / 100, 2) }}
</p>

<p>
    <a href="{{ route('bookings.show', $booking->reference) }}">View your booking details</a>
</p>
