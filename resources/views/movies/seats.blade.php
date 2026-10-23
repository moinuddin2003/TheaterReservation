@extends('layouts.cinema')

@section('title', 'Select seats — '.$movie['title'])

@section('content')
    @php
        $seatMap = $movie['seat_map'];
        $prices = $seatMap['prices'];
        $firstDate = $movie['showtimes'][0];
        $firstScreening = collect($firstDate['screenings'])->first(fn ($screening) => ! ($screening['closed'] ?? false))
            ?? ['time' => '—', 'sales_close' => '—'];
    @endphp

    <div class="page-shell seat-page">
        <p class="booking-steps"><strong>01 SELECT SEATS</strong><span aria-hidden="true">→</span> 02 CHECKOUT <span aria-hidden="true">→</span> 03 YOUR TICKETS</p>
        <div class="seat-page__heading">
            <div>
                <p class="eyebrow">{{ $movie['title'] }} <span aria-hidden="true">/</span> {{ $firstDate['screen'] }}</p>
                <h1>A great night starts with a great seat</h1>
            </div>
            <a class="back-link" href="{{ route('movies.show', $movie['slug']) }}"><span aria-hidden="true">←</span> Back to showtimes</a>
        </div>

        <div class="seat-date-tabs" role="tablist" aria-label="Choose a date">
            @foreach ($movie['showtimes'] as $showingDate)
                <button
                    class="date-tab {{ $loop->first ? 'is-active' : '' }}"
                    type="button"
                    role="tab"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                    data-seat-date="{{ $showingDate['date'] }}"
                    data-seat-date-label="{{ $showingDate['seat_date_label'] }}"
                    data-seat-date-short="{{ $showingDate['date_label'] }}"
                    data-seat-screen="{{ $showingDate['screen'] }}"
                >
                    {{ $showingDate['weekday_short'] }} <span>·</span> {{ $showingDate['date_short'] }}
                </button>
            @endforeach
        </div>

        <div class="seat-layout">
            <aside class="booking-sidebar" aria-label="Movie and booking details">
                <div class="movie-mini">
                    <x-movie-image :src="$movie['images']['poster']" :alt="$movie['title'].' poster'" class="movie-mini__art" />
                    <div>
                        <p class="eyebrow" data-movie-screen>{{ $firstDate['screen'] }}</p>
                        <h2>{{ $movie['title'] }}</h2>
                        <div class="movie-meta movie-meta--compact">
                            <span class="rating-pill">{{ $movie['rating'] }}</span>
                            <span>{{ $movie['runtime_minutes'] }} min</span>
                            <span>{{ implode(' . ', $movie['genres']) }}</span>
                        </div>
                    </div>
                </div>

                <div class="showtime-picker">
                    <p class="showtime-picker__date" data-seat-date-display>{{ $firstDate['seat_date_label'] }}</p>
                    @foreach ($movie['showtimes'] as $showingDate)
                        <div
                            class="showtime-picker__buttons"
                            aria-label="Choose a showtime for {{ $showingDate['date_label'] }}"
                            data-time-panel="{{ $showingDate['date'] }}"
                            @if (! $loop->first) hidden @endif
                        >
                            @foreach ($showingDate['screenings'] as $screening)
                                @if ($screening['closed'] ?? false)
                                    <button type="button" disabled>{{ $screening['time'] }} <span>· Closed</span></button>
                                @else
                                    <button
                                        type="button"
                                        data-time-button="{{ $screening['time'] }}"
                                        data-sales-close="{{ $screening['sales_close'] }}"
                                        aria-pressed="false"
                                    >{{ $screening['time'] }}</button>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <x-movie-selection-card :prices="$prices" :max-selection="$seatMap['max_selection']" />

                <div class="sign-in-note">
                    <strong><span aria-hidden="true">ⓘ</span> Sign in to hold seats</strong>
                    <p>If you’re browsing as a guest, sign in first. You’ll bring your bag to this showtime. Your email must be verified.</p>
                </div>
                <p class="hold-note">Your 10-minute hold starts only after you choose Hold seats. All seats are held together or none are.</p>
            </aside>

            <div class="seat-content">
                <x-movie-seat-map :seat-map="$seatMap" :prices="$prices" :screen="$firstDate['screen']" />

                <div class="screening-summary">
                    <div>
                        <p><span data-screening-date>{{ $firstDate['date_label'] }}</span> <span aria-hidden="true">·</span> <span data-screening-time>{{ $firstScreening['time'] }}</span></p>
                        <p>
                            <span data-screening-screen>{{ $firstDate['screen'] }}</span>
                            <span aria-hidden="true">·</span> {{ count($seatMap['rows']) }} rows × {{ $seatMap['seats_per_row'] }} seats
                            <span aria-hidden="true">·</span> Sales close <span data-sales-close-summary>{{ $firstScreening['sales_close'] }}</span>
                        </p>
                    </div>
                    <p class="availability"><span aria-hidden="true">✓</span> <strong data-available-count>—</strong> seats available</p>
                </div>
            </div>
        </div>
    </div>
@endsection
