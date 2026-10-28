@extends('layouts.cinema')

@section('title', 'Select seats — '.$movie->title)

@section('content')
    <div class="page-shell seat-page">
        <p class="booking-steps"><strong>01 SELECT SEATS</strong><span aria-hidden="true">→</span> 02 CHECKOUT <span aria-hidden="true">→</span> 03 YOUR TICKETS</p>
        <div class="seat-page__heading">
            <div>
                <p class="eyebrow">{{ $movie->title }} <span aria-hidden="true">/</span> {{ $screen->name }}</p>
                <h1>A great night starts with a great seat</h1>
            </div>
            <a class="back-link" href="{{ route('movies.show', $movie) }}"><span aria-hidden="true">←</span> Back to showtimes</a>
        </div>

        <div class="seat-layout">
            <aside class="booking-sidebar" aria-label="Movie and booking details">
                <div class="movie-mini">
                    <x-movie-image :src="$movie->posterUrl() ?? $movie->poster_path" :alt="$movie->title.' poster'" class="movie-mini__art" />
                    <div>
                        <p class="eyebrow" data-movie-screen>{{ $screen->name }}</p>
                        <h2>{{ $movie->title }}</h2>
                        <x-movie-meta :movie="$movie" compact />
                    </div>
                </div>

                <div class="showtime-picker">
                    <p class="showtime-picker__date" data-seat-date-display>{{ $showtime->starts_at->format('l, F j, Y') }}</p>
                    <div class="showtime-picker__buttons">
                        @if ($salesClosed)
                            <button type="button" disabled>{{ $showtime->starts_at->format('H:i') }} <span>· Closed</span></button>
                        @else
                            <button
                                type="button"
                                data-time-button="{{ $showtime->starts_at->format('H:i') }}"
                                data-sales-close="{{ $salesCloseAt }}"
                                aria-pressed="true"
                            >{{ $showtime->starts_at->format('H:i') }}</button>
                        @endif
                    </div>
                </div>

                @php
                    $customer = auth()->user();
                    $requiresSeatHoldPreparation = auth()->guest()
                        || ($customer?->isCustomer() && ! $customer->hasVerifiedEmail());
                @endphp
                <x-movie-selection-card
                    :prices="$prices"
                    :max-selection="$maxSelection"
                    :showtime="$showtime"
                    :requires-seat-hold-preparation="$requiresSeatHoldPreparation"
                />

                @if ($requiresSeatHoldPreparation)
                    <div class="sign-in-note">
                        <strong>
                            <span aria-hidden="true">ⓘ</span>
                            @if (auth()->guest())
                                Sign in to continue selecting seats
                            @else
                                Verify your email to continue selecting seats
                            @endif
                        </strong>
                        <p>
                            @if (auth()->guest())
                                After you sign in, your selected seats will be marked on the seat map. You can change them before choosing Hold.
                            @else
                                After you verify your email, your selected seats will be marked on the seat map. You can change them before choosing Hold.
                            @endif
                        </p>
                    </div>
                @endif
                <p class="hold-note">Your {{ $holdMinutes }}-minute hold starts only after you choose Hold seats. All seats are held together or none are.</p>
            </aside>

            <div class="seat-content">
                <x-movie-seat-map :seat-map="$seatMap" :prices="$prices" :screen="$screen->name" />

                <div class="screening-summary">
                    <div>
                        <p><span data-screening-date>{{ $showtime->starts_at->format('l, F j') }}</span> <span aria-hidden="true">·</span> <span data-screening-time>{{ $showtime->starts_at->format('H:i') }}</span></p>
                        <p>
                            <span data-screening-screen>{{ $screen->name }}</span>
                            <span aria-hidden="true">·</span> {{ count($seatMap['rows']) }} rows × {{ $seatMap['seats_per_row'] }} seats
                            <span aria-hidden="true">·</span> Sales close <span data-sales-close-summary>{{ $salesCloseAt }}</span>
                        </p>
                    </div>
                    <p class="availability"><span aria-hidden="true">✓</span> <strong data-available-count>{{ $availableSeats }}</strong> seats available</p>
                </div>
            </div>
        </div>
    </div>
@endsection
