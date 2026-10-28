@props(['date', 'showtimes', 'availability' => [], 'active' => false, 'premiumSurchargeCents' => 0])

@php
    $firstShowtime = $showtimes->first();
    $screen = $firstShowtime?->screen;
    $standardPriceCents = (int) ($firstShowtime?->base_price_cents ?? 0);
    $premiumPriceCents = $standardPriceCents + (int) $premiumSurchargeCents;
@endphp

<article class="showtime-card" data-day-panel="{{ $date }}" @if (! $active) hidden @endif>
    <div class="showtime-card__details">
        <h3>{{ $screen?->name ?? 'Screen' }}</h3>
        <p>
            Standard ${{ number_format($standardPriceCents / 100, 2) }}
            <span aria-hidden="true">·</span>
            Premium ${{ number_format($premiumPriceCents / 100, 2) }}
        </p>
    </div>
    <div class="showtime-card__times" aria-label="Showtimes on {{ $date }}">
        @foreach ($showtimes as $showtime)
            @php($state = $availability[$showtime->id] ?? ['available' => 0, 'sold_out' => false])
            @if ($showtime->hasSalesClosed())
                <button class="showtime-button is-closed" type="button" disabled>
                    <span>{{ $showtime->starts_at->format('g:i A') }}</span>
                    <small>Sales closed</small>
                </button>
            @elseif ($state['sold_out'])
                <button class="showtime-button is-closed" type="button" disabled>
                    <span>{{ $showtime->starts_at->format('g:i A') }}</span>
                    <small>Sold out</small>
                </button>
            @else
                <a class="showtime-button" href="{{ route('showtimes.show', $showtime) }}">
                    <span>{{ $showtime->starts_at->format('g:i A') }}</span>
                    <small>{{ $state['available'] }} seats</small>
                </a>
            @endif
        @endforeach
    </div>
</article>
