@props(['booking'])

@php($movie = $booking->showtime->movie)

<article class="booking-movie-card">
    <x-movie-image
        :src="$movie->poster_path"
        :alt="$movie->title.' poster'"
        class="booking-movie-card__poster"
    />
    <div class="booking-movie-card__details">
        <h2>{{ $movie->title }}</h2>
        <p>
            {{ $booking->showtime->starts_at->format('D, M j, Y · H:i') }}
            <span aria-hidden="true">·</span>
            {{ $booking->showtime->screen->name }}
        </p>
        <p>
            {{ $booking->items->pluck('seat_label')->join(', ') ?: 'Seat details unavailable' }}
            <span aria-hidden="true">·</span>
            {{ $booking->seats_count }} {{ str('seat')->plural($booking->seats_count) }}
        </p>
        <p>{{ $booking->reference }}</p>
        <p>
            Total {{ number_format($booking->total_cents / 100, 2) }}
            {{ strtoupper($booking->currency) }}
        </p>
    </div>
</article>
