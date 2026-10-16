<p>Hello {{ $booking->user->name }},</p>
<p>Booking <strong>{{ $booking->reference }}</strong> for {{ $booking->showtime->movie->title }} has been cancelled.</p>
<p>{{ $booking->cancel_reason }}</p>
<p>Any eligible refund will be processed separately and confirmed by email.</p>
