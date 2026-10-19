@extends('layouts.cinema')

@section('title', 'CineBook — Your next great escape')

@section('content')
    @php
        $firstDate = $featuredMovie['showtimes'][0] ?? null;
        $firstShowtime = $firstDate ? collect($firstDate['screenings'])->first(fn ($screening) => ! ($screening['closed'] ?? false)) : null;
    @endphp

    <section class="home-hero">
        <x-movie-image :src="$featuredMovie['images']['cover']" :alt="$featuredMovie['title'].' cover'" class="home-hero__image" />
        <div class="home-hero__shade"></div>
        <div class="home-hero__content page-shell">
            <p class="eyebrow">ONLY AT {{ strtoupper(config('cinema.venue')) }}</p>
            <p class="eyebrow">01 <span aria-hidden="true">·</span> CUSTOMER <span aria-hidden="true">/</span> HOME <span aria-hidden="true">·</span> NOW SHOWING</p>
            <h1>{{ $featuredMovie['title'] }}</h1>
            <p class="home-hero__meta">
                {{ $featuredMovie['rating'] }} <span aria-hidden="true">·</span>
                {{ $featuredMovie['runtime_minutes'] }} min <span aria-hidden="true">·</span>
                {{ implode(' / ', $featuredMovie['genres']) }}
            </p>
            <p class="home-hero__synopsis">{{ $featuredMovie['synopsis'] }}</p>
            <div class="home-hero__actions">
                @if ($firstDate && $firstShowtime)
                    <a class="button button--primary" href="{{ route('movies.seats', ['movieSlug' => $featuredMovie['slug'], 'date' => $firstDate['date'], 'time' => $firstShowtime['time']]) }}">Find seats <span aria-hidden="true">→</span></a>
                @endif
                <a class="button button--quiet" href="{{ route('movies.show', ['movieSlug' => $featuredMovie['slug']]) }}">Movie details <span aria-hidden="true">→</span></a>
            </div>
            @if ($firstDate && $firstShowtime)
                <p class="home-hero__showtime">Tonight · {{ $firstDate['screen'] }} · {{ $firstShowtime['time'] }} · From ${{ $featuredMovie['seat_map']['prices']['standard'] }}</p>
            @endif
        </div>
    </section>

    <div class="page-shell home-content">
        <section class="home-schedule" aria-labelledby="schedule-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">MAKE IT A MOVIE NIGHT</p>
                    <h2 id="schedule-title">What’s on at Downtown?</h2>
                </div>
                <a class="text-link" href="{{ route('movies.index') }}">All movies <span aria-hidden="true">→</span></a>
            </div>
            <div class="home-date-list" role="tablist" aria-label="Choose a show date">
                @foreach ($dates as $date)
                    <button class="home-date {{ $loop->first ? 'is-active' : '' }}" type="button" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-home-date="{{ $date['date'] }}">
                        <span>{{ $date['weekday'] }}</span><strong>{{ $date['label'] }}</strong>
                    </button>
                @endforeach
            </div>
            @foreach ($dates as $date)
                @php
                    $dateSchedule = collect($featuredMovie['showtimes'])->firstWhere('date', $date['date']);
                @endphp
                <div class="home-schedule__row" data-home-schedule="{{ $date['date'] }}" @if (! $loop->first) hidden @endif>
                    @if ($dateSchedule)
                        <p><strong>{{ $featuredMovie['title'] }}</strong> <span aria-hidden="true">·</span> {{ $dateSchedule['screen'] }} <span aria-hidden="true">·</span> Standard ${{ $featuredMovie['seat_map']['prices']['standard'] }} / Premium ${{ $featuredMovie['seat_map']['prices']['premium'] }}</p>
                        <div class="home-schedule__times">
                            @foreach ($dateSchedule['screenings'] as $screening)
                                @if ($screening['closed'] ?? false)
                                    <span class="home-schedule__closed">{{ $screening['time'] }} · Closed</span>
                                @else
                                    <a href="{{ route('movies.seats', ['movieSlug' => $featuredMovie['slug'], 'date' => $dateSchedule['date'], 'time' => $screening['time']]) }}">{{ $screening['time'] }}</a>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p>Showtimes for {{ $date['weekday'] }}, {{ $date['label'] }} will be announced soon.</p>
                    @endif
                </div>
            @endforeach
        </section>

        <section class="catalog-section" aria-labelledby="now-showing-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">FIND YOUR NEXT ESCAPE</p>
                    <h2 id="now-showing-title">Now showing</h2>
                </div>
                <a class="text-link" href="{{ route('movies.index') }}">Explore all movies <span aria-hidden="true">→</span></a>
            </div>
            <div class="movie-grid movie-grid--five">
                @foreach ($nowShowing as $movie)
                    <x-movie-card :movie="$movie" />
                @endforeach
            </div>
        </section>

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
        </section>

        <section class="mood-panel" aria-label="Browse movies by genre">
            <div>
                <p class="eyebrow">FIND YOUR MOOD</p>
                <h2>A story for every kind of night</h2>
            </div>
            <div class="genre-links">
                @foreach ($genres as $genre)
                    <a href="{{ route('movies.index', ['genre' => $genre]) }}">{{ $genre }} <span aria-hidden="true">→</span></a>
                @endforeach
            </div>
        </section>
    </div>
@endsection
