@extends('layouts.cinema')

@section('title', 'Movies')

@section('content')
    <div class="page-shell catalog-page">
        <p class="eyebrow">MOVIES <span aria-hidden="true">/</span> {{ $cinema?->name ?? config('app.name') }}</p>
        <div class="section-heading catalog-page__heading">
            <div>
                <h1>Find your next escape</h1>
                <p>Discover what’s playing and what’s next. All showtimes are local to Downtown.</p>
            </div>
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
                        <option value="{{ strtolower($genre->slug) }}">{{ $genre->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <p class="catalog-summary" data-catalog-summary>{{ $movies->total() }} movies now showing</p>
        <div class="movie-grid movie-grid--catalog">
            @foreach ($movies as $movie)
                <x-movie-card :movie="$movie" />
            @endforeach
        </div>
        @if ($movies->isEmpty())
            <p class="catalog-empty" data-catalog-empty>No movies match your filters.</p>
        @endif

        <div class="catalog-pagination">
            {{ $movies->links() }}
        </div>
    </div>  
@endsection