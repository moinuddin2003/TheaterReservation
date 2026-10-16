<x-admin.layout :title="$showtime->movie->title" breadcrumb="Cinema">
    <x-admin.page-header :title="$showtime->movie->title" subtitle="{{ $showtime->starts_at->format('l, M j, Y · g:i A') }} · {{ $showtime->screen->name }}">
        <x-slot:actions>
            <x-admin.button variant="secondary" href="{{ route('admin.showtimes.index') }}">Back to schedule</x-admin.button>
            @if ($showtime->status->value === 'scheduled' && $showtime->starts_at->isFuture())<x-admin.button variant="secondary" href="{{ route('admin.showtimes.edit', $showtime) }}">Edit</x-admin.button>@endif
        </x-slot:actions>
    </x-admin.page-header>
    <x-admin.toast />
    @php($capacity = $showtime->screen->seats->count())
    @php($booked = $seats->where('occupancy_state', 'booked')->count())
    @php($held = $seats->where('occupancy_state', 'held')->count())
    @php($salesClosed = $showtime->starts_at->subMinutes(15)->isPast())
    @php($soldOut = $capacity > 0 && $booked + $held >= $capacity)
    @php($ended = $showtime->ends_at->isPast())

    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat-card label="Sold seats" value="{{ $booked }} / {{ $capacity }}" icon="ticket" />
        <x-admin.stat-card label="Seats held" :value="number_format($held)" icon="clock" />
        <x-admin.stat-card label="Collected" value="${{ number_format($salesCents / 100, 2) }}" icon="dollar-sign" />
        <x-admin.stat-card label="Tickets scanned today" :value="number_format($scannedCount)" icon="scan-line" />
    </div>
    <div class="mb-5 flex flex-wrap gap-2">
        <x-admin.status-badge :status="$showtime->status" />
        @if ($salesClosed)<x-admin.status-badge status="Sales closed" />@endif
        @if ($soldOut)<x-admin.status-badge status="Sold out" />@endif
        @if ($ended)<x-admin.status-badge status="Ended" />@endif
    </div>
    <x-admin.card title="Live seat map" subtitle="Occupancy reflects active bookings, unexpired holds, and disabled seats.">
        <x-admin.seat-map :seats="$seats" />
    </x-admin.card>
    @if ($showtime->status->value === 'scheduled' && $showtime->starts_at->isFuture())
        <x-admin.confirm-dialog :action="route('admin.showtimes.cancel', $showtime)" title="Cancel showtime" message="This will cancel the screening and notify affected customers." reason>
            <x-admin.button variant="danger" x-on:click="open = true">Cancel showtime</x-admin.button>
        </x-admin.confirm-dialog>
    @endif
</x-admin.layout>
