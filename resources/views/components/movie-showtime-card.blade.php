@props(['movie', 'showingDate', 'prices', 'active' => false])

<article class="showtime-card" data-day-panel="{{ $showingDate['date'] }}" @if (! $active) hidden @endif>
    <div class="showtime-card__details">
        <h3>{{ $showingDate['date_label'] }}</h3>
        <p>
            {{ $showingDate['screen'] }}
            <span aria-hidden="true">·</span>
            Standard ${{ $prices['standard'] }}
            <span aria-hidden="true">·</span>
            Premium ${{ $prices['premium'] }}
        </p>
    </div>
    <div class="showtime-card__times" aria-label="Showtimes {{ $showingDate['date_label'] }}">
        @foreach ($showingDate['screenings'] as $screening)
            @if ($screening['closed'] ?? false)
                <button class="showtime-button is-closed" type="button" disabled>
                    <span>{{ $screening['time'] }}</span><small>Sales closed</small>
                </button>
            @else
                <a
                    class="showtime-button {{ ($screening['featured'] ?? false) ? 'is-featured' : '' }}"
                    href="{{ route('movies.seats', ['movieSlug' => $movie['slug'], 'date' => $showingDate['date'], 'time' => $screening['time']]) }}"
                >
                    <span>{{ $screening['time'] }}</span><small>{{ $screening['available_seats'] }} seats</small>
                </a>
            @endif
        @endforeach
    </div>
</article>
