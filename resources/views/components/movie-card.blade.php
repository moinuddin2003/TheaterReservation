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

<article class="movie-card" data-movie-card data-movie-title="{{ strtolower($movie->title) }}"
    data-movie-genres="{{ strtolower($movie->genres->pluck('name')->implode(' ')) }}"
    data-movie-status="{{ $upcoming ? 'coming-soon' : 'now-showing' }}">
    <a class="movie-card__image-link" href="{{ route('movies.show', ['movieSlug' => $movie->slug]) }}">
        <x-movie-image :src="$movie->posterUrl() ?? $movie->poster_path" :alt="$movie->title . ' poster'"
            class="movie-card__image" />
    </a>
    <h3><a href="{{ route('movies.show', ['movieSlug' => $movie->slug]) }}">{{ $movie->title }}</a></h3>
    @if ($upcoming)
        <p>{{ $movie->release_date?->format('M j, Y') }}</p>
    @else
        <x-movie-meta :movie="$movie" compact />

        @if ($showtime)
            <p>{{ $showtimeDate }} · {{ $showtime->starts_at->format('H:i') }}</p>
        @endif
    @endif
</article>