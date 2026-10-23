@props(['prices', 'maxSelection', 'showtime'])

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
    <form action="{{ route('showtimes.holds.store', $showtime) }}" method="POST" data-hold-form>
        @csrf
        <div data-hold-inputs hidden></div>
        <button class="button button--primary button--full" type="submit" data-hold-submit disabled>
            Hold <span data-hold-count>0</span> seats <span aria-hidden="true">·</span> $<span data-hold-price>0</span> <span aria-hidden="true">→</span>
        </button>
    </form>
    @error('seat_ids')
        <p class="form-error" role="alert">{{ $message }}</p>
    @enderror
</section>
