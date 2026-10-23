@props(['show', 'lookup' => false])

<form class="staff-panel scan-next" method="POST" action="{{ route('staff.scan.store') }}" autocomplete="off">
    @csrf
    <input type="hidden" name="show" value="{{ $show['id'] }}">

    <x-staff.field :focus="true" label="Next ticket token · focused and ready" for="ticket-token">
        <input
            id="ticket-token"
            class="staff-field__control"
            type="text"
            name="token"
            placeholder="Scan next ticket or type token…"
            aria-describedby="scan-hint"
            autocapitalize="off"
            spellcheck="false"
            autofocus
            required
            data-scan-input
        >
    </x-staff.field>

    <p class="scan-next__hint" id="scan-hint">
        @if ($lookup)
            Submit with Enter · <x-staff.link to="staff.lookup" class="scan-next__link">Reference lookup available</x-staff.link>
        @else
            USB keyboard scanner / manual token · Press Enter to submit.
        @endif
    </p>
</form>
