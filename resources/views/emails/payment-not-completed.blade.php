<h1>Your payment was not completed</h1>

<p>Hello {{ $booking->user->name }},</p>

<p>
    We could not complete your payment for {{ $booking->showtime->movie->title }}.
    The seat hold has now been released, so those seats are no longer reserved for you.
</p>

<p>Booking reference: <strong>{{ $booking->reference }}</strong></p>

<p>
    If you still want to attend, please choose available seats and start a new booking.
    If your account was charged after the hold expired, we will automatically request a refund.
</p>

<p><a href="{{ route('movies.index') }}">Browse movies and showtimes</a></p>
