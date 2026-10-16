<p>Hello {{ $booking->user->name }},</p>
<p>Your payment has been confirmed for booking <strong>{{ $booking->reference }}</strong>.</p>
<p>{{ $booking->showtime->movie->title }} · {{ $booking->showtime->starts_at->utc()->format('Y-m-d H:i') }} UTC</p>
<ul>
    @foreach ($booking->items as $item)
        <li>Seat {{ $item->seat_label }} @if ($item->ticket) — ticket {{ $item->ticket->ticket_number }} @endif</li>
    @endforeach
</ul>
