@props(['movie', 'showingDate', 'prices', 'active' => false])

<section
    class="showtime-card"
    data-showtime-panel="{{ $showingDate['date'] }}"
    aria-label="{{ $showingDate['date_label'] }} showtimes"
    @if (! $active) hidden @endif
>
    <div class="showtime-card__details">
        <p>{{ $showingDate['screen'] }}</p>
        <p>Standard ${{ $prices['standard'] }} <span aria-hidden="true">·</span> Premium ${{ $prices['premium'] }}</p>
    </div>
    <div class="showtime-card__times">
        @foreach ($showingDate['screenings'] as $screening)
            @if ($screening['closed'] ?? false)
                <span class="showtime-card__closed">{{ $screening['time'] }} · Closed</span>
            @else
                <a href="{{ route('movies.seats', ['movieSlug' => $movie['slug'], 'date' => $showingDate['date'], 'time' => $screening['time']]) }}">
                    {{ $screening['time'] }}
                </a>
            @endif
        @endforeach
    </div>
</section>
