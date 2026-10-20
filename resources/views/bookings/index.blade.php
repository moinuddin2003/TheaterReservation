@extends('layouts.cinema')

@section('title', 'My bookings — '.config('cinema.brand'))

@section('content')
    <div class="page-shell booking-page">
        <p class="eyebrow">ACCOUNT <span aria-hidden="true">/</span> MY BOOKINGS</p>
        <h1 class="booking-page__title">Your movie nights</h1>
        <p class="booking-page__intro">Review your bookings and ticket status.</p>

        <nav class="booking-filters" aria-label="Filter bookings">
            @foreach ([
                'upcoming' => ['Upcoming', $upcomingCount],
                'past' => ['Past', $pastCount],
                'all' => ['All', $allCount],
            ] as $key => [$label, $count])
                <a
                    class="booking-filter {{ $filter === $key ? 'is-active' : '' }}"
                    href="{{ route('bookings.index', ['filter' => $key]) }}"
                    @if ($filter === $key) aria-current="page" @endif
                >
                    {{ $label }} <span>{{ $count }}</span>
                </a>
            @endforeach
        </nav>

        @forelse ($bookings as $booking)
            <section class="booking-list-card">
                <div class="booking-list-card__heading">
                    <x-booking-status :status="$booking->status" />
                    <span>{{ $booking->reference }}</span>
                </div>
                <x-booking-summary :booking="$booking" />
                <div class="booking-list-card__actions">
                    @if ($booking->isConfirmed())
                        <a
                            class="button button--primary"
                            href="{{ route('bookings.confirmed', $booking->reference) }}"
                        >View tickets <span aria-hidden="true">→</span></a>
                    @elseif ($booking->isPendingPayment())
                        <a
                            class="button button--primary"
                            href="{{ route('bookings.processing', $booking->reference) }}"
                        >Check payment status <span aria-hidden="true">→</span></a>
                    @endif
                    <a
                        class="button button--quiet"
                        href="{{ route('bookings.show', $booking->reference) }}"
                    >Booking details</a>
                </div>
            </section>
        @empty
            <section class="booking-empty">
                <h2>No bookings to show</h2>
                <p>Your bookings will appear here after they are recorded.</p>
                <a class="button button--primary" href="{{ route('movies.index') }}">Browse movies</a>
            </section>
        @endforelse

        @if ($bookings->hasPages())
            <nav class="booking-pagination" aria-label="Booking pages">
                @if ($bookings->onFirstPage())
                    <button class="button button--quiet" type="button" disabled>← Previous</button>
                @else
                    <a class="button button--quiet" href="{{ $bookings->previousPageUrl() }}">← Previous</a>
                @endif

                <span>Page {{ $bookings->currentPage() }} of {{ $bookings->lastPage() }}</span>

                @if ($bookings->hasMorePages())
                    <a class="button button--quiet" href="{{ $bookings->nextPageUrl() }}">Next →</a>
                @else
                    <button class="button button--quiet" type="button" disabled>Next →</button>
                @endif
            </nav>
        @endif
    </div>
@endsection
