@extends('layouts.cinema')

@section('title', 'Home — '.config('cinema.brand'))

@section('content')
    @if ($featured)
        @php($featuredShowtime = $featured->showtimes->first())

        <section class="home-hero">
            @if ($featured->backdropUrl() || $featured->posterUrl())
                <x-movie-image
                    :src="$featured->backdropUrl() ?? $featured->posterUrl()"
                    :alt="$featured->title.' artwork'"
                    class="home-hero__image"
                />
            @endif
            <div class="home-hero__shade"></div>
            <div class="home-hero__content page-shell">
                <p class="eyebrow">ONLY AT {{ strtoupper($cinema?->name ?? config('cinema.venue')) }}</p>
                <p class="eyebrow">01 <span aria-hidden="true">·</span> CUSTOMER <span aria-hidden="true">/</span> HOME <span aria-hidden="true">·</span> NOW SHOWING</p>
                <h1>{{ $featured->title }}</h1>
                <x-movie-meta :movie="$featured" />
                <p class="home-hero__synopsis">{{ $featured->synopsis }}</p>
                <div class="home-hero__actions">
                    @if ($featuredShowtime)
                        <a class="button button--primary" href="{{ route('showtimes.show', $featuredShowtime) }}">Find seats <span aria-hidden="true">→</span></a>
                    @endif
                    <a class="button button--quiet" href="{{ route('movies.show', $featured) }}">Movie details <span aria-hidden="true">→</span></a>
                </div>
                @if ($featuredShowtime)
                    <p class="home-hero__showtime">
                        {{ $featuredShowtime->starts_at->format('D, M j · H:i') }}
                        · {{ $featuredShowtime->screen->name }}
                        · From ${{ number_format($featuredShowtime->base_price_cents / 100, 2) }}
                    </p>
                @endif
            </div>
        </section>
    @else
        <section class="home-hero">
            <div class="home-hero__content page-shell">
                <p class="eyebrow">CUSTOMER <span aria-hidden="true">/</span> HOME</p>
                <h1>No movies are showing right now</h1>
                <p class="home-hero__synopsis">Please check back soon for new movies and showtimes.</p>
            </div>
        </section>
    @endif

    <div class="page-shell home-content">
        @if ($featured && $featured->showtimes->isNotEmpty())
            <section class="home-schedule" aria-labelledby="schedule-title">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">MAKE IT A MOVIE NIGHT</p>
                        <h2 id="schedule-title">Upcoming showtimes</h2>
                    </div>
                    <a class="text-link" href="{{ route('movies.index') }}">All movies <span aria-hidden="true">→</span></a>
                </div>
                <div class="home-schedule__times">
                    @foreach ($featured->showtimes as $showtime)
                        <a href="{{ route('showtimes.show', $showtime) }}">
                            {{ $showtime->starts_at->format('D, M j · H:i') }} · {{ $showtime->screen->name }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="catalog-section" aria-labelledby="now-showing-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">FIND YOUR NEXT ESCAPE</p>
                    <h2 id="now-showing-title">Now showing</h2>
                </div>
                <a class="text-link" href="{{ route('movies.index', ['status' => 'now_showing']) }}">Explore all movies <span aria-hidden="true">→</span></a>
            </div>
            <div class="movie-grid movie-grid--five">
                @forelse ($nowShowing as $movie)
                    <x-movie-card :movie="$movie" />
                @empty
                    <p>No movies are currently marked as now showing.</p>
                @endforelse
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
                @forelse ($comingSoon as $movie)
                    <x-movie-card :movie="$movie" upcoming />
                @empty
                    <p>No upcoming movies have been announced yet.</p>
                @endforelse
            </div>
        </section>

        @if ($genres->isNotEmpty())
            <section class="mood-panel" aria-label="Browse movies by genre">
                <div>
                    <p class="eyebrow">FIND YOUR MOOD</p>
                    <h2>A story for every kind of night</h2>
                </div>
                <div class="genre-links">
                    @foreach ($genres as $genre)
                        <a href="{{ route('movies.index', ['genre' => $genre->slug]) }}">{{ $genre->name }} <span aria-hidden="true">→</span></a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
