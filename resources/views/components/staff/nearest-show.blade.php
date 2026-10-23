@props(['show', 'now'])

@php
    $isOpen = $show['status'] === 'open';
@endphp

<section class="staff-panel nearest-show" aria-labelledby="nearest-show-title">
    <div class="nearest-show__banner">
        <p class="nearest-show__state {{ $isOpen ? 'is-open' : '' }}">
            {{ $isOpen ? '✓ ENTRY OPEN · NEAREST SHOW' : 'ENTRY NOT OPEN YET · NEXT SHOW' }}
        </p>
        <p class="nearest-show__clock">Local time · {{ $now->format('H:i') }}</p>
    </div>

    <h2 class="nearest-show__title" id="nearest-show-title">{{ $show['movie'] }}</h2>

    <div class="nearest-show__row">
        <p class="nearest-show__meta">
            {{ $show['screen'] }} · {{ $show['time'] }}<br>
            Entry {{ $show['window'] }} · {{ $show['scanned'] }} / {{ $show['sold'] }} scanned
        </p>
        <div class="staff-actions">
            <x-staff.link to="staff.scan" :params="['show' => $show['id']]" class="button button--primary">Scan this show →</x-staff.link>
            <x-staff.link to="staff.roster" :params="['show' => $show['id']]" class="button button--quiet">View roster</x-staff.link>
        </div>
    </div>
</section>
