@props(['movie', 'compact' => false])

<div {{ $attributes->class(['movie-meta', 'movie-meta--compact' => $compact]) }}>
    @unless ($compact)
        <span class="status-pill"><span class="status-dot"></span> {{ $movie->status }}</span>
    @endunless
    <span class="rating-pill">{{ $movie->rating }}</span>
    <span>{{ $movie->duration_minutes }} min</span>
    <span>{{ $movie->genres->pluck('name')->implode(' . ') }}</span>
</div>