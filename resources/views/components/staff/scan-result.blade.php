@props(['result'])

<section class="scan-result" role="status" aria-labelledby="scan-result-title">
    <div class="scan-result__head">
        <img class="scan-result__icon scan-result__icon--dark" src="{{ asset('images/staff/circle-check-dark.svg') }}" alt="" width="72" height="72">
        <img class="scan-result__icon scan-result__icon--light" src="{{ asset('images/staff/circle-check-light.svg') }}" alt="" width="72" height="72">
        <h2 id="scan-result-title">ADMITTED</h2>
    </div>

    <p class="scan-result__show">{{ $result['movie'] }} · {{ $result['screen'] }}</p>

    <div class="scan-result__seat-row">
        <p class="scan-result__seat">{{ $result['seat'] }}</p>
        <p class="scan-result__details">
            {{ $result['seat_label'] }}<br><br>
            Ticket {{ $result['ticket'] }} · Admitted at {{ $result['admitted_at'] }}
        </p>
    </div>

    <p class="scan-result__message">Single-use ticket accepted. This admission can’t be undone.</p>
</section>
