<h1>Booking cancelled</h1>
<p>Hello {{ $booking->user->name }},</p>
<p>Your booking <strong>{{ $booking->reference }}</strong> for {{ $booking->showtime->movie->title }} has been cancelled.</p>
<p>Showtime: {{ $booking->showtime->starts_at->format('D, j M Y · H:i') }}<br>
    {{ $booking->showtime->screen->cinema->name }} · {{ $booking->showtime->screen->name }}</p>
<p>If a refund was requested, its status will be updated separately. Contact the cinema if you need help.</p>
