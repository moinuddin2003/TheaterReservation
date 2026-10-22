@props(['shows', 'selected'])

@php
    $isOpen = $selected['status'] === 'open';
@endphp

<form class="scan-picker" method="GET" action="{{ route('staff.scan') }}">
    <x-staff.field class="scan-picker__field" label="Showtime · nearest by default" for="show-picker">
        <select id="show-picker" class="staff-field__control" name="show" data-auto-submit>
            @foreach ($shows as $show)
                <option value="{{ $show['id'] }}" @selected($show['id'] === $selected['id'])>
                    {{ $show['movie'] }} · {{ $show['time'] }} · {{ $show['screen'] }}
                </option>
            @endforeach
        </select>
    </x-staff.field>

    <p class="scan-picker__state {{ $isOpen ? 'is-open' : '' }}">
        {{ $isOpen ? '✓ Entry open' : 'Entry opens '.$selected['opens_at'] }}
    </p>

    <noscript><button class="button button--quiet" type="submit">Switch show</button></noscript>
</form>
