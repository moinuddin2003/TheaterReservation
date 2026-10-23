@props(['show'])

<form class="scan-focus" method="POST" action="{{ route('staff.scan.store') }}" autocomplete="off">
    @csrf
    <input type="hidden" name="show" value="{{ $show['id'] }}">

    <div class="scan-focus__title">
        <img src="{{ asset('images/staff/scan-line.svg') }}" alt="" width="40" height="40">
        <h2>Scan a ticket</h2>
    </div>

    <x-staff.field :focus="true" label="Ticket token · always focused" for="ticket-token">
        <input
            id="ticket-token"
            class="staff-field__control"
            type="text"
            name="token"
            placeholder="Waiting for USB scanner or manual entry…"
            aria-describedby="scan-hint"
            autocapitalize="off"
            spellcheck="false"
            autofocus
            required
            data-scan-input
        >
    </x-staff.field>

    <p class="scan-focus__hint" id="scan-hint">
        Keyboard-mode USB scanner: scan then Enter. Or type the ticket token and press Enter. Focus returns here after every result.
    </p>

    <button class="button button--primary" type="submit">Submit token · Enter ↵</button>
</form>
