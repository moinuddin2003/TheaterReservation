<h1>Booking confirmed</h1>
<p>Hello {{ $booking->user->name }},</p>
<p>Your booking <strong>{{ $booking->reference }}</strong> is confirmed.</p>
<p>{{ $booking->showtime->movie->title }}<br>
    {{ $booking->showtime->starts_at->format('D, j M Y · H:i') }}<br>
    {{ $booking->showtime->screen->cinema->name }} · {{ $booking->showtime->screen->name }}</p>
<p>Seats: {{ $booking->items->pluck('seat_label')->join(', ') }}</p>
<p>Total: {{ $booking->currency }} {{ number_format($booking->total_cents / 100, 2) }}</p>
