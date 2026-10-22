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
            @php($seatsByNumber = collect($seatMap['seats'][$row])->keyBy('number'))
            @foreach (range(1, $seatMap['seats_per_row']) as $number)
                @if ($seatsByNumber->has($number))
                    @php($seat = $seatsByNumber->get($number))
                    <x-movie-seat
                        :row="$row"
                        :number="$seat['number']"
                        :type="$seat['type']"
                        :status="$seat['status']"
                        :seat-id="$seat['id']"
                    />
                @else
                    <span class="seat seat--spacer" aria-hidden="true"></span>
                @endif
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
