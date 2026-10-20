@props(['reference' => ''])

{{-- One GET form, so a lookup can be reloaded or shared between staff. --}}
<form class="lookup-form" method="GET" action="{{ route('staff.lookup') }}" role="search" autocomplete="off">
    <x-staff.field :focus="true" label="Booking reference" for="lookup-reference">
        <input
            id="lookup-reference"
            class="staff-field__control"
            type="text"
            name="reference"
            value="{{ $reference }}"
            placeholder="CB-261015-0482"
            maxlength="{{ \App\Services\Staff\BookingLookupService::MAX_REFERENCE_LENGTH }}"
            autocapitalize="characters"
            spellcheck="false"
            autofocus
            required
        >
    </x-staff.field>

    <button class="button button--primary" type="submit">Look up reference →</button>
</form>
