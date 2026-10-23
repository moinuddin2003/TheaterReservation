@props(['movie', 'compact' => false])

@php
    $rating = data_get($movie, 'rating');
    $runtime = data_get($movie, 'runtime_minutes', data_get($movie, 'duration_minutes'));
    $genres = collect(data_get($movie, 'genres', []))
        ->map(fn ($genre) => is_string($genre) ? $genre : data_get($genre, 'name'))
        ->filter()
        ->implode(' / ');
@endphp

<p {{ $attributes->class(['movie-meta', 'movie-meta--compact' => $compact]) }}>
    @if ($rating)
        <span>{{ $rating }}</span>
        <span aria-hidden="true">·</span>
    @endif
    @if ($runtime)
        <span>{{ $runtime }} min</span>
    @endif
    @if ($genres !== '')
        <span aria-hidden="true">·</span>
        <span>{{ $genres }}</span>
    @endif
</p>
