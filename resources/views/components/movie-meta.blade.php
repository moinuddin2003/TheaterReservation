@props(['movie', 'compact' => false])

<p {{ $attributes->class(['movie-meta', 'movie-meta--compact' => $compact]) }}>
    <span>{{ $movie['rating'] }}</span>
    <span aria-hidden="true">·</span>
    <span>{{ $movie['runtime_minutes'] }} min</span>
    <span aria-hidden="true">·</span>
    <span>{{ implode(' / ', $movie['genres']) }}</span>
</p>
