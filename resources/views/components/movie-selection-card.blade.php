@props(['prices', 'maxSelection'])

<section class="selection-card" aria-label="Seat selection">
    <h3>Your selection</h3>
    <p data-selected-seats>No seats selected</p>
    <p>Choose up to {{ $maxSelection }} seats.</p>
    <p>
        Standard ${{ $prices['standard'] }}
        <span aria-hidden="true">·</span>
        Premium ${{ $prices['premium'] }}
    </p>
    <p>Total <strong data-selection-total>$0</strong></p>
</section>
