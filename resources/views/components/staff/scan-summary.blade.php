@props(['show', 'now'])

<section class="staff-panel scan-summary" aria-labelledby="scan-summary-title">
    <p class="scan-summary__screen">• {{ $show['screen'] }}</p>
    <p class="scan-summary__count">{{ $show['scanned'] }} / {{ $show['sold'] }}</p>
    <p class="scan-summary__caption">Guests admitted / tickets sold</p>
    <h2 class="scan-summary__title" id="scan-summary-title">{{ $show['movie'] }}</h2>
    <p class="scan-summary__details">
        {{ $show['starts_at']->format('D, M j') }} · Starts {{ $show['time'] }}<br>
        Entry opens {{ $show['opens_at'] }}<br>
        Entry closes {{ $show['closes_at'] }}<br>
        Current time {{ $now->format('H:i') }}
    </p>
    <div class="staff-actions">
        <x-staff.link to="staff.roster" :params="['show' => $show['id']]" class="button button--quiet">View roster</x-staff.link>
    </div>
</section>
