@extends('layouts.cinema')

@section('title', 'Movies — '.config('cinema.brand'))

@section('content')
    <div class="page-shell catalog-page">
        <p class="eyebrow">MOVIES <span aria-hidden="true">/</span> {{ config('cinema.venue') }}</p>
        <div class="section-heading catalog-page__heading">
            <div>
                <h1>Find your next escape</h1>
                <p>Discover what’s playing and what’s next. All showtimes are local to Downtown.</p>
            </div>
        </div>

        <div class="catalog-tabs" role="tablist" aria-label="Filter movies by release status">
            <button class="catalog-tab is-active" type="button" role="tab" aria-selected="true" data-catalog-status="now-showing">Now showing <span>{{ $nowShowing->count() }}</span></button>
            <button class="catalog-tab" type="button" role="tab" aria-selected="false" data-catalog-status="coming-soon">Coming soon <span>{{ $comingSoon->count() }}</span></button>
        </div>

        <div class="catalog-filters" role="search">
            <label class="catalog-filter">
                <span>Search movies</span>
                <input type="search" placeholder="Search by title..." data-movie-search>
            </label>
            <label class="catalog-filter">
                <span>Genre</span>
                <select data-movie-genre>
                    <option value="">All genres</option>
                    @foreach ($genres as $genre)
                        <option value="{{ strtolower($genre) }}">{{ $genre }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <p class="catalog-summary" data-catalog-summary>{{ $nowShowing->count() }} movies now showing</p>
        <div class="movie-grid movie-grid--catalog">
            @foreach ($nowShowing as $movie)
                <x-movie-card :movie="$movie" />
            @endforeach
            @foreach ($comingSoon as $movie)
                <x-movie-card :movie="$movie" upcoming />
            @endforeach
        </div>
        <p class="catalog-empty" data-catalog-empty hidden>No movies match your filters.</p>
        <div class="catalog-pagination"><span data-catalog-count>Showing {{ $nowShowing->count() }} movies</span><div><button class="button button--quiet" type="button" disabled>← Previous</button><span>1</span><button class="button button--quiet" type="button" disabled>Next →</button></div></div>
    </div>
@endsection
