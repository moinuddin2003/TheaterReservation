@props(['prices', 'maxSelection'])

<section
    class="selection-card"
    aria-labelledby="selection-title"
    data-standard-price="{{ $prices['standard'] }}"
    data-premium-price="{{ $prices['premium'] }}"
    data-max-selection="{{ $maxSelection }}"
>
    <h2 id="selection-title">Your selection</h2>
    <p class="selection-card__seats" data-selection-list>No seats selected</p>
    <p class="selection-card__price"><span data-seat-count>0</span> × $<span data-unit-price>0</span> <span aria-hidden="true">·</span> <strong>$<span data-total-price>0</span></strong></p>
    <p class="selection-card__hint">
        Standard ${{ $prices['standard'] }} <span aria-hidden="true">·</span>
        Premium ${{ $prices['premium'] }}. Select 1–{{ $maxSelection }} seats. Not held yet.
    </p>
    <button class="button button--primary button--full" type="button" data-hold-submit data-demo-message="This is a UI preview. Seat holds and checkout will be connected later." disabled>
        Hold <span data-hold-count>0</span> seats <span aria-hidden="true">·</span> $<span data-hold-price>0</span> <span aria-hidden="true">→</span>
    </button>
</section>
