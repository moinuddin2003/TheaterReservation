@props(['state', 'reference' => ''])

<section class="staff-panel scan-empty" aria-live="polite">
    @if ($state === 'not_found')
        <h2>No booking found</h2>
        <p>There is no confirmed booking with the reference “{{ $reference }}”. Check it for typos, or ask the guest to open My bookings.</p>
    @else
        <h2>Enter a booking reference</h2>
        <p>Find it on the guest’s confirmation or in My bookings. It looks like CB-261015-0482.</p>
    @endif

    <div class="staff-actions">
        <x-staff.link to="staff.scan" class="button button--quiet">Return to focused scanner →</x-staff.link>
    </div>
</section>
