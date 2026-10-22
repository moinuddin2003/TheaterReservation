@props(['rejection'])

<section class="scan-result scan-result--reject scan-result--{{ $rejection['tone'] }}" role="alert" aria-labelledby="scan-result-title">
    <x-staff.reject-icon :status="$rejection['status']" />

    <p class="scan-result__label">{{ $rejection['label'] }}</p>
    <h2 id="scan-result-title">{{ $rejection['headline'] }}</h2>
    <p class="scan-result__detail">{{ $rejection['detail'] }}</p>
    <p class="scan-result__context">{{ $rejection['context'] }}</p>
</section>
