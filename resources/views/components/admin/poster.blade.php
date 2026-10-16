@props(['src' => null, 'title' => 'Movie', 'size' => 'md'])

@php
    $initials = collect(str($title)->explode(' '))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    $dimensions = $size === 'sm' ? 'h-12 w-9 text-xs' : 'h-36 w-24 text-2xl';
@endphp

@if ($src)
    <img src="{{ $src }}" alt="Poster for {{ $title }}" loading="lazy" class="{{ $dimensions }} rounded-lg object-cover">
@else
    <div role="img" aria-label="Poster placeholder for {{ $title }}" class="{{ $dimensions }} flex shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-cine-selected-start to-cine-selected-end font-bold text-white">{{ $initials }}</div>
@endif
