@extends('layouts.cinema')

@section('title', $movie['title'].' — '.config('cinema.brand'))

@section('content')
    @php($prices = $movie['seat_map']['prices'] ?? null)

    <div class="page-shell movie-page">
        <p class="eyebrow">MOVIES <span aria-hidden="true">/</span> {{ $movie['title'] }}</p>

        <x-movie-image :src="$movie['images']['cover']" :alt="$movie['title'].' cover'" class="movie-cover" />

        <section class="movie-intro" aria-labelledby="movie-title">
            <x-movie-image :src="$movie['images']['poster']" :alt="$movie['title'].' poster'" class="movie-poster" />
            <div class="movie-intro__body">
                <x-movie-meta :movie="$movie" />
                <h1 id="movie-title">{{ $movie['title'] }}</h1>
                <p class="movie-summary">{{ $movie['synopsis'] }}</p>
                <button class="button button--quiet" type="button" data-demo-message="Trailer preview will be added soon.">
                    <span aria-hidden="true">▷</span> Watch trailer <span class="button__subtle">· HD</span>
                </button>
            </div>
        </section>

        @if ($prices && count($movie['showtimes']))
        <section class="showtimes-section" id="showtimes" aria-labelledby="showtimes-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">PLAN YOUR VISIT</p>
                    <h2 id="showtimes-title">Choose your showtime</h2>
                </div>
                <p class="section-note"><span aria-hidden="true">⌖</span> All times local <span aria-hidden="true">·</span> {{ config('cinema.venue') }}</p>
            </div>

            <div class="date-tabs" role="tablist" aria-label="Choose a date">
                @foreach ($movie['showtimes'] as $showingDate)
                    <button
                        class="date-tab {{ $loop->first ? 'is-active' : '' }}"
                        type="button"
                        role="tab"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        data-day-button="{{ $showingDate['date'] }}"
                    >
                        <span>{{ $showingDate['weekday_short'] }}</span><strong>{{ $showingDate['date_short'] }}</strong>
                    </button>
                @endforeach
            </div>

            <div class="showtime-list">
                @foreach ($movie['showtimes'] as $showingDate)
                    <x-movie-showtime-card
                        :movie="$movie"
                        :showing-date="$showingDate"
                        :prices="$prices"
                        :active="$loop->first"
                    />
                @endforeach
            </div>
            <p class="sales-note">Sales close 15 minutes before each show. Select a time to preview seat selection.</p>
        </section>
        @else
            <section class="showtimes-section">
                <p class="release-note">Showtimes will be announced soon.</p>
                <a class="button button--quiet" href="{{ route('movies.index') }}">Browse all movies</a>
            </section>
        @endif
    </div>
@endsection
