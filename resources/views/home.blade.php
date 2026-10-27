@extends('layouts.cinema')

@section('title', 'CineBook — Your next great escape')

@section('content')

    {{-- HERO SECTION --}}
    @if ($featured)
        <section class="home-hero">
            <x-movie-image :src="$featured->backdropUrl() ?? $featured->posterUrl()" :alt="$featured->title . ' cover'"
                class="home-hero__image" />
            <div class="home-hero__shade"></div>
            <div class="home-hero__content page-shell">
                <p class="eyebrow">
                    ONLY AT {{ strtoupper($cinema?->name ?? 'CINEBOOK') }}
                </p>
                <p class="eyebrow">01 <span aria-hidden="true">·</span> CUSTOMER <span aria-hidden="true">/</span> HOME <span
                        aria-hidden="true">·</span> NOW SHOWING</p>

                <h1>{{ $featured->title }}</h1>

                <p class="home-hero__meta">
                    {{ $featured->rating }} <span aria-hidden="true">·</span>
                    {{ $featured->duration_minutes }} min <span aria-hidden="true">·</span>
                    {{ $featured->genres->pluck('name')->implode(' / ') }}
                </p>

                <p class="home-hero__synopsis">{{ $featured->synopsis }}</p>

                <div class="home-hero__actions">
                    <a class="button button--primary" href="{{ route('movies.show', $featured->slug) }}">
                        Find Seats <span aria-hidden="true">→</span>
                    </a>
                    <a class="button button--quiet" href="{{ route('movies.show', $featured->slug) }}">
                        Movie details <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </section>
    @endif

    <div class="page-shell home-content">


        {{-- DYNAMIC CINEMA SCHEDULE --}}
        <section class="home-schedule" aria-labelledby="schedule-title">

            <div class="section-heading">
                <div>
                    <p class="eyebrow">MAKE IT A MOVIE NIGHT</p>
                    <h2 id="schedule-title">
                        What's on at {{ $cinema?->name ?? 'CineBook' }}?
                    </h2>
                </div>

                <a class="text-link" href="{{ route('movies.index') }}">All movies <span aria-hidden="true">→</span></a>
            </div>

            {{-- DATE TABS --}}
            <div class="home-date-list" role="tablist" aria-label="Choose a show date">

                @foreach ($dates as $date)
                    <button class="home-date {{ $loop->first ? 'is-active' : '' }}" id="date-tab-{{ $date['date'] }}"
                        type="button" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        aria-controls="schedule-{{ $date['date'] }}" data-home-date="{{ $date['date'] }}">

                        <span>{{ $date['weekday'] }}</span>
                        <strong>{{ $date['label'] }}</strong>
                    </button>
                @endforeach

            </div>

            {{-- SHOWTIMES FOR EACH DATE --}}
            @foreach ($dates as $date)

                @php
                    $dateShowtimes = $showtimesByDate->get(
                        $date['date'],
                        collect()
                    );

                    $bookableShowtimes = $dateShowtimes
                        ->filter(fn($showtime) => $showtime->isBookable());
                @endphp

                <div class="home-schedule__row" id="schedule-{{ $date['date'] }}" role="tabpanel"
                    aria-labelledby="date-tab-{{ $date['date'] }}" data-home-schedule="{{ $date['date'] }}" @if (!$loop->first)
                    hidden @endif>

                    <div class="home-schedule__details">
                        <p class="home-schedule__movie">
                            <strong>{{ $bookableShowtimes->count() }}</strong>
                            <span aria-hidden="true"> · </span>
                            {{ $bookableShowtimes->count() === 1
                ? 'showtime available'
                : 'showtimes available' }}
                        </p>

                        <div class="home-schedule__times">

                            @forelse ($bookableShowtimes as $showtime)
                                <a href="{{ route('showtimes.show', $showtime) }}" class="home-schedule__time">
                                    <strong>{{ $showtime->movie->title }}</strong>
                                    <span>{{ $showtime->starts_at->format('H:i') }}</span>
                                    <small>{{ $showtime->screen?->name ?? 'Screen' }}</small>
                                </a>
                            @empty
                                <p class="home-schedule__empty">
                                    No bookable showtimes for this date.
                                    Please check another day.
                                </p>
                            @endforelse

                        </div>
                    </div>
                </div>

            @endforeach

        </section>


        {{-- NOW SHOWING SECTION --}}
        <section class="catalog-section" aria-labelledby="now-showing-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">FIND YOUR NEXT ESCAPE</p>
                    <h2 id="now-showing-title">Now showing</h2>
                </div>
                <a class="text-link" href="{{ route('movies.index') }}">Explore all movies <span
                        aria-hidden="true">→</span></a>
            </div>
            <div class="movie-grid movie-grid--five">
                @foreach ($nowShowing as $movie)
                    <x-movie-card :movie="$movie" />
                @endforeach
            </div>
            @if ($nowShowing->isEmpty())
                <p class="release-note">There are no movies listed as now showing yet.</p>
            @endif
        </section>

        {{-- COMING SOON SECTION --}}
        <section class="catalog-section" aria-labelledby="coming-soon-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">NEW STORIES ON THE HORIZON</p>
                    <h2 id="coming-soon-title">Coming soon</h2>
                </div>
            </div>
            <div class="coming-grid">
                @foreach ($comingSoon as $movie)
                    <x-movie-card :movie="$movie" upcoming />
                @endforeach
            </div>
            @if ($comingSoon->isEmpty())
                <p class="release-note">There are no upcoming movies listed yet.</p>
            @endif
        </section>

        {{-- GENRES SECTION --}}
        <section class="mood-panel" aria-label="Browse movies by genre">
            <div>
                <p class="eyebrow">FIND YOUR MOOD</p>
                <h2>A story for every kind of night</h2>
            </div>
            <div class="genre-links">
                @foreach ($genres as $genre)
                    <a href="{{ route('movies.index', ['genre' => $genre->slug ?? $genre->name]) }}">
                        {{ $genre->name }} <span aria-hidden="true">→</span>
                    </a>
                @endforeach
                @if ($genres->isEmpty())
                    <p class="release-note">Movie genres will appear here.</p>
                @endif
            </div>
        </section>

    </div>
@endsection