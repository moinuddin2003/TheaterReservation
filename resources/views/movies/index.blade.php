@extends('layouts.cinema')

@section('title', $movie->title)

@section('content')
    @php($firstDate = $showtimesByDate->keys()->first())

    <div class="page-shell movie-page">
        <p class="eyebrow">MOVIES <span aria-hidden="true">/</span> {{ $movie->title }}</p>

        <x-movie-image :src="$movie->backdropUrl() ?? $movie->poster_path" :alt="$movie->title.' cover'" class="movie-cover" />

        <section class="movie-intro" aria-labelledby="movie-title">
            <x-movie-image :src="$movie->posterUrl() ?? $movie->poster_path" :alt="$movie->title.' poster'" class="movie-card__image" />

            <div class="movie-intro__body">
                <x-movie-meta :movie="$movie" />
                <h1 id="movie-title">{{ $movie->title }}</h1>
                <p class="movie-summary">{{ $movie->synopsis }}</p>
                @if (filled($movie->trailer_url))
                    <a class="button button--quiet" href="{{ $movie->trailer_url }}"
                        target="_blank" rel="noopener noreferrer">
                        <span aria-hidden="true">▷</span> Watch trailer
                    </a>
                @endif
            </div>
        </section>

        @if ($showtimesByDate->isNotEmpty())
            <section class="showtimes-section" id="showtimes" aria-labelledby="showtimes-title">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">PLAN YOUR VISIT</p>
                        <h2 id="showtimes-title">Choose your showtime</h2>
                    </div>
                    <p class="section-note"><span aria-hidden="true">⌖</span> All times local <span aria-hidden="true">·</span> {{ $cinema?->name ?? config('app.name') }}</p>
                </div>

                <div class="date-tabs" role="tablist" aria-label="Choose a date">
                    @foreach ($showtimesByDate as $date => $dayShowtimes)
                        <button
                            class="date-tab {{ $loop->first ? 'is-active' : '' }}"
                            type="button"
                            role="tab"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            data-day-button="{{ $date }}"
                        >
                            <span>{{ \Illuminate\Support\Carbon::parse($date)->format('D') }}</span>
                            <strong>{{ \Illuminate\Support\Carbon::parse($date)->format('M j') }}</strong>
                        </button>
                    @endforeach
                </div>

                <div class="showtime-list">
                    @foreach ($showtimesByDate as $date => $dayShowtimes)
                        @foreach ($dayShowtimes->groupBy('screen_id') as $screenShowtimes)
                            <x-movie-showtime-card
                                :date="$date"
                                :showtimes="$screenShowtimes"
                                :availability="$availability"
                                :active="$date === $firstDate"
                                :premium-surcharge-cents="$premiumSurchargeCents"
                            />
                        @endforeach
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
