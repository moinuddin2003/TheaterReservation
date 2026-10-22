@props(['seatMap', 'prices', 'screen'])

<section
    class="seat-map-card"
    aria-labelledby="seat-map-title"
    data-standard-price="{{ $prices['standard'] }}"
    data-premium-price="{{ $prices['premium'] }}"
>
    <div class="screen-curve">
        <span id="seat-map-title">{{ $screen }}</span>
    </div>
    <p class="screen-caption">Front of auditorium</p>

    <div
        class="seat-grid"
        role="group"
        aria-label="{{ $screen }} seat map"
        data-seat-map
        style="--seat-cols: {{ $seatMap['seats_per_row'] }}"
    >
        @foreach ($seatMap['rows'] as $row => $type)
            <span class="row-label">{{ $row }}</span>
            @foreach (range(1, $seatMap['seats_per_row']) as $number)
                @php
                    $seatLabel = $row.$number;
                    $seatStatus = match (true) {
                        in_array($seatLabel, $seatMap['held'], true) => 'held',
                        in_array($seatLabel, $seatMap['sold'], true) => 'sold',
                        in_array($seatLabel, $seatMap['disabled'], true) => 'disabled',
                        in_array($seatLabel, $seatMap['selected'], true) => 'selected',
                        default => 'available',
                    };
                @endphp
                <x-movie-seat :row="$row" :number="$number" :type="$type" :status="$seatStatus" />
            @endforeach
            <span class="row-label">{{ $row }}</span>
        @endforeach
    </div>

    <p class="premium-note">
        {{ implode('–', array_keys($seatMap['rows'], 'premium', true)) }}
        <span aria-hidden="true">·</span> Premium seats
        <span aria-hidden="true">·</span> ${{ $prices['premium'] }} per seat
    </p>

    <div class="seat-legend" aria-label="Seat availability legend">
        <span><i class="legend-seat legend-seat--available"></i> Available</span>
        <span><i class="legend-seat legend-seat--held"></i> Held</span>
        <span><i class="legend-seat legend-seat--sold"></i> Sold</span>
        <span><i class="legend-seat legend-seat--disabled"></i> Disabled</span>
        <span><i class="legend-seat legend-seat--selected"></i> Your selection</span>
    </div>
</section>
