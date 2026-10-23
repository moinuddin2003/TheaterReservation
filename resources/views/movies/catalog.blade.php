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

        <form class="catalog-filters" role="search" method="GET" action="{{ route('movies.index') }}">
            <label class="catalog-filter">
                <span>Search movies</span>
                <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search by title...">
            </label>
            <label class="catalog-filter">
                <span>Genre</span>
                <select name="genre">
                    <option value="">All genres</option>
                    @foreach ($genres as $genre)
                        <option value="{{ $genre->slug }}" @selected($filters['genre'] === $genre->slug)>{{ $genre->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="catalog-filter">
                <span>Availability</span>
                <select name="status">
                    <option value="">All movies</option>
                    <option value="now_showing" @selected($filters['status'] === 'now_showing')>Now showing</option>
                    <option value="coming_soon" @selected($filters['status'] === 'coming_soon')>Coming soon</option>
                </select>
            </label>
            <button class="button button--primary" type="submit">Apply filters</button>
            @if ($filters['search'] || $filters['genre'] || $filters['status'])
                <a class="button button--quiet" href="{{ route('movies.index') }}">Clear filters</a>
            @endif
        </form>

        <p class="catalog-summary">
            {{ $movies->total() }} {{ \Illuminate\Support\Str::plural('movie', $movies->total()) }} found
        </p>
        <div class="movie-grid movie-grid--catalog">
            @forelse ($movies as $movie)
                <x-movie-card :movie="$movie" :upcoming="$movie->status === \App\Enums\MovieStatus::ComingSoon" />
            @empty
                <p>No movies match your filters.</p>
            @endforelse
        </div>
        <div class="catalog-pagination">{{ $movies->links() }}</div>
    </div>
@endsection
