@props(['src', 'alt'])

<img src="{{ asset($src) }}" alt="{{ $alt }}" {{ $attributes->class(['movie-image']) }}>
