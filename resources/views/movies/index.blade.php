@extends('layouts.cinema')

@section('title', $movie->title.' — '.config('cinema.brand'))

@section('content')
    <div class="page-shell movie-page">
        <p class="eyebrow">MOVIES <span aria-hidden="true">/</span> {{ $movie->title }}</p>

        @if ($movie->backdropUrl())
            <x-movie-image :src="$movie->backdropUrl()" :alt="$movie->title.' cover'" class="movie-cover" />
        @endif

        <section class="movie-intro" aria-labelledby="movie-title">
            @if ($movie->posterUrl())
                <x-movie-image :src="$movie->posterUrl()" :alt="$movie->title.' poster'" class="movie-poster" />
            @endif
            <div class="movie-intro__body">
                <x-movie-meta :movie="$movie" />
                <h1 id="movie-title">{{ $movie->title }}</h1>
                <p class="movie-summary">{{ $movie->synopsis }}</p>
                @if ($movie->trailer_url)
                    <a class="button button--quiet" href="{{ $movie->trailer_url }}" target="_blank" rel="noopener noreferrer">
                        <span aria-hidden="true">▷</span> Watch trailer <span class="button__subtle">· HD</span>
                    </a>
                @endif
            </div>
        </section>

        <section class="showtimes-section" id="showtimes" aria-labelledby="showtimes-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">PLAN YOUR VISIT</p>
                    <h2 id="showtimes-title">Choose your showtime</h2>
                </div>
                <p class="section-note"><span aria-hidden="true">⌖</span> Upcoming scheduled showtimes</p>
            </div>

            @forelse ($showtimesByDate as $date => $showtimes)
                <div class="showtime-list">
                    <h3>{{ \Illuminate\Support\Carbon::parse($date)->format('l, F j') }}</h3>
                    <div class="showtime-card__times">
                        @foreach ($showtimes as $showtime)
                            <a href="{{ route('showtimes.show', $showtime) }}">
                                {{ $showtime->starts_at->format('H:i') }}
                                · {{ $showtime->screen->name }}
                                · From ${{ number_format($showtime->base_price_cents / 100, 2) }}
                                @if (($availability[$showtime->id]['sold_out'] ?? false))
                                    · Sold out
                                @else
                                    · {{ $availability[$showtime->id]['available'] ?? 0 }} seats available
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="release-note">Showtimes will be announced soon.</p>
                <a class="button button--quiet" href="{{ route('movies.index') }}">Browse all movies</a>
            @endforelse
        </section>
    </div>
@endsection
