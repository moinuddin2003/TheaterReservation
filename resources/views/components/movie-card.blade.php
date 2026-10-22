@props(['movie', 'upcoming' => false])

<article
    class="movie-card"
    data-movie-card
    data-movie-title="{{ strtolower($movie['title']) }}"
    data-movie-genres="{{ strtolower(implode(' ', $movie['genres'])) }}"
    data-movie-status="{{ $upcoming ? 'coming-soon' : 'now-showing' }}"
>
    <a class="movie-card__image-link" href="{{ route('movies.show', ['movieSlug' => $movie['slug']]) }}">
        <x-movie-image :src="$movie['images']['poster']" :alt="$movie['title'].' poster'" class="movie-card__image" />
    </a>
    <h3><a href="{{ route('movies.show', ['movieSlug' => $movie['slug']]) }}">{{ $movie['title'] }}</a></h3>
    @if ($upcoming)
        <p>{{ $movie['release_date'] }}</p>
    @else
        <x-movie-meta :movie="$movie" compact />
        @if (count($movie['showtimes']))
            <p>Today · {{ collect($movie['showtimes'][0]['screenings'])->pluck('time')->join(' / ') }}</p>
        @else
            <p>Showtimes available soon</p>
        @endif
    @endif
</article>
