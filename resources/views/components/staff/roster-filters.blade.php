@props(['shows', 'selected', 'query'])

{{-- One GET form: changing the show reloads at page 1, Enter in the search box applies the search. --}}
<form class="roster-filters" method="GET" action="{{ route('staff.roster') }}" role="search" autocomplete="off">
    <x-staff.field class="roster-filters__show" label="Showtime" for="roster-show">
        <select id="roster-show" class="staff-field__control" name="show" onchange="this.form.requestSubmit()">
            @foreach ($shows as $show)
                <option value="{{ $show['id'] }}" @selected($show['id'] === $selected['id'])>
                    {{ $show['movie'] }} · {{ $show['starts_at']->format('M j') }} · {{ $show['time'] }} · {{ $show['screen'] }}
                </option>
            @endforeach
        </select>
    </x-staff.field>

    <x-staff.field label="Search name or reference" for="roster-search">
        <input id="roster-search" class="staff-field__control" type="search" name="q" value="{{ $query }}" placeholder="Search roster…" spellcheck="false">
    </x-staff.field>

    <button class="sr-only" type="submit">Apply</button>
</form>
