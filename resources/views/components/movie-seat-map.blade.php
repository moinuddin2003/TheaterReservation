@props(['seatMap', 'prices', 'screen'])

<section class="seat-map" aria-label="Choose seats for {{ $screen }}">
    <p class="seat-map__screen">{{ $screen }}</p>
    <div class="seat-map__rows">
        @foreach ($seatMap['rows'] as $row => $tier)
            <div class="seat-map__row" aria-label="Row {{ $row }}">
                <span class="seat-map__row-label" aria-hidden="true">{{ $row }}</span>
                @for ($number = 1; $number <= $seatMap['seats_per_row']; $number++)
                    @php
                        $seat = $row.$number;
                        $status = in_array($seat, $seatMap['held'], true) ? 'held'
                            : (in_array($seat, $seatMap['sold'], true) ? 'sold'
                                : (in_array($seat, $seatMap['disabled'], true) ? 'disabled'
                                    : (in_array($seat, $seatMap['selected'], true) ? 'selected' : 'available')));
                        $unavailable = in_array($status, ['held', 'sold', 'disabled'], true);
                    @endphp
                    <button
                        type="button"
                        class="seat-map__seat seat-map__seat--{{ $status }} seat-map__seat--{{ $tier }}"
                        data-seat="{{ $seat }}"
                        data-seat-price="{{ $prices[$tier] }}"
                        aria-label="Row {{ $row }}, seat {{ $number }}, {{ $status }}, ${{ $prices[$tier] }}"
                        aria-pressed="{{ $status === 'selected' ? 'true' : 'false' }}"
                        @if ($unavailable) disabled @endif
                    >{{ $number }}</button>
                @endfor
                <span class="seat-map__row-label" aria-hidden="true">{{ $row }}</span>
            </div>
        @endforeach
    </div>
    <div class="seat-map__legend" aria-label="Seat availability">
        <span>Available</span>
        <span>Selected</span>
        <span>Unavailable</span>
    </div>
</section>
