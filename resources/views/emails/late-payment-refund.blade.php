<h1>Your late payment and refund update</h1>

<p>Hello {{ $refund->booking->user->name }},</p>

<p>
    We received your payment for {{ $refund->booking->showtime->movie->title }} after the seat hold had expired,
    so those seats were no longer available and the booking could not be confirmed.
</p>

@if ($refund->isSucceeded())
    <p>
        Stripe confirmed the automatic refund of
        {{ strtoupper($refund->currency) }} {{ number_format($refund->amount_cents / 100, 2) }}.
        It may take several business days for your bank to show the funds.
    </p>
@elseif ($refund->isFailed())
    <p>
        We could not complete the automatic refund of
        {{ strtoupper($refund->currency) }} {{ number_format($refund->amount_cents / 100, 2) }}.
        Please contact the cinema and include your booking reference so we can help.
    </p>
@else
    <p>
        We have requested an automatic refund of
        {{ strtoupper($refund->currency) }} {{ number_format($refund->amount_cents / 100, 2) }}.
        The request is being processed. Your bank may take several business days to show the funds.
    </p>
@endif

<p>Booking reference: <strong>{{ $refund->booking->reference }}</strong></p>

<p><a href="{{ route('bookings.show', $refund->booking->reference) }}">View booking details</a></p>
