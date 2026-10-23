@props(['src' => null, 'alt' => ''])

@if (filled($src))
    @php($imageUrl = str_starts_with($src, 'http://') || str_starts_with($src, 'https://') || str_starts_with($src, '/') ? $src : asset($src))
    <img src="{{ $imageUrl }}" alt="{{ $alt }}" {{ $attributes }}>
@endif
