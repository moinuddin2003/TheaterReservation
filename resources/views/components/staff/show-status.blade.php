@props(['shows', 'selected', 'now'])

<section class="staff-panel scan-status" aria-label="Show status">
    <form method="GET" action="{{ route('staff.scan') }}">
        <x-staff.field class="scan-picker__field" label="Showtime" for="show-status-picker">
            <select id="show-status-picker" class="staff-field__control" name="show" data-auto-submit>
                @foreach ($shows as $show)
                    <option value="{{ $show['id'] }}" @selected($show['id'] === $selected['id'])>
                        {{ $show['movie'] }} · {{ $show['time'] }}
                    </option>
                @endforeach
            </select>
        </x-staff.field>
        <noscript><button class="button button--quiet" type="submit">Switch show</button></noscript>
    </form>

    <div class="scan-status__figures">
        <p>{{ $selected['status'] === 'open' ? '✓ Entry open' : 'Entry opens '.$selected['opens_at'] }}</p>
        <p class="scan-status__count">{{ $selected['scanned'] }} / {{ $selected['sold'] }}</p>
        <p>Scanned / sold tickets</p>
        <p>
            {{ $selected['screen'] }} · Entry {{ $selected['window'] }}<br>
            Current time {{ $now->format('H:i') }}
        </p>
    </div>

    <div class="staff-actions">
        <x-staff.link to="staff.roster" :params="['show' => $selected['id']]" class="button button--quiet">View roster</x-staff.link>
    </div>
</section>
