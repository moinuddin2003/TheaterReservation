@props(['movie', 'compact' => false])

<div {{ $attributes->class(['movie-meta', 'movie-meta--compact' => $compact]) }}>
    @unless ($compact)
        <span class="status-pill"><span class="status-dot"></span> {{ $movie['status'] }}</span>
    @endunless
    <span class="rating-pill">{{ $movie['rating'] }}</span>
    <span>{{ $movie['runtime_minutes'] }} min</span>
    <span>{{ implode(' · ', $movie['genres']) }}</span>
</div>
