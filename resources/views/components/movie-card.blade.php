@props(['movie', 'upcoming' => false])

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
        @if ($movie->upcoming_showtimes_count > 0)
            <p>{{ $movie->upcoming_showtimes_count }} upcoming showtimes</p>
        @else
            <p>Showtimes available soon</p>
        @endif
    @endif
</article>
