@props(['movie', 'upcoming' => false])

@php
    $showtime = $movie->relationLoaded('showtimes')
        ? $movie->showtimes->first()
        : null;
    $showtimeDate = null;

    if ($showtime !== null) {
        $showtimeDate = $showtime->starts_at->format('M j');

        if ($showtime->starts_at->isToday()) {
            $showtimeDate = 'Today';
        } elseif ($showtime->starts_at->isTomorrow()) {
            $showtimeDate = 'Tomorrow';
        }
    }
@endphp

<article class="movie-card">
    <a class="movie-card__image-link" href="{{ route('movies.show', $movie) }}">
        @if ($movie->posterUrl())
            <x-movie-image :src="$movie->posterUrl()" :alt="$movie->title.' poster'" class="movie-card__image" />
        @else
            <span class="movie-card__image">{{ $movie->title }}</span>
        @endif
    </a>
    <h3><a href="{{ route('movies.show', $movie) }}">{{ $movie->title }}</a></h3>
    @if ($upcoming)
        <p>{{ $movie->release_date?->format('M j, Y') ?? 'Release date to be announced' }}</p>
    @else
        <x-movie-meta :movie="$movie" compact />
        @if ($showtime)
            <p>{{ $showtimeDate }} · {{ $showtime->starts_at->format('H:i') }}</p>
        @endif
    @endif
</article>
