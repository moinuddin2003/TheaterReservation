@props(['booking', 'venue'])

<section class="staff-panel lookup-booking" aria-label="Booking {{ $booking['ref'] }}">
    <div class="lookup-booking__head">
        <p class="lookup-booking__status">✓ Confirmed booking</p>
        <p class="lookup-booking__ref">{{ $booking['ref'] }}</p>
    </div>

    <h2 class="lookup-booking__name">{{ $booking['name'] }}</h2>
    <p class="lookup-booking__show">
        {{ $booking['movie'] }} · {{ $booking['date'] }} · {{ $booking['time'] }} {{ $venue }} · {{ $booking['screen'] }}
    </p>

    <x-staff.lookup-tickets :tickets="$booking['tickets']" />

    <x-staff.notice title="Damaged QR assistance">
        Ask the guest to open My bookings → Tickets to access the individual ticket. The token can also be entered at the scanner. This lookup does not admit a guest.
    </x-staff.notice>

    <div class="staff-actions">
        <x-staff.link to="staff.scan" :params="['show' => $booking['show_id']]" class="button button--quiet">Return to focused scanner →</x-staff.link>
    </div>
</section>
