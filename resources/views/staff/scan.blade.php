@extends('layouts.staff')

@section('title', 'Scan tickets')
@section('active', 'scan')

@section('content')
    <x-staff.page-head title="Ready when they are." :date="$now" />

    @if ($scan['selected'])
        <div class="scan-layout">
            <div class="scan-layout__main">
                <section class="staff-panel scan-panel" aria-label="Ticket scanner">
                    <x-staff.show-picker :shows="$scan['shows']" :selected="$scan['selected']" />
                    <x-staff.scan-form :show="$scan['selected']" />
                    <p class="scan-panel__footnote">No camera needed · Single-use admission · No undo or reset</p>
                </section>

                <h2 class="scan-layout__heading">Recent 10 attempts</h2>
                <x-staff.attempts-table :attempts="$scan['attempts']" />
            </div>

            <aside class="scan-layout__side">
                <x-staff.scan-summary :show="$scan['selected']" :now="$now" />

                <x-staff.notice title="Damaged QR?">
                    Search by booking reference to find the guest’s seat and ticket status. No payment information is needed.
                </x-staff.notice>

                <div class="staff-actions">
                    <x-staff.link to="staff.lookup" class="button button--quiet">Reference lookup →</x-staff.link>
                </div>
            </aside>
        </div>

        @push('scripts')
            <x-staff.scan-focus-script />
        @endpush
    @else
        <section class="staff-panel scan-empty">
            <h2>No shows to scan right now</h2>
            <p>Entry has closed for every show today.</p>
            <div class="staff-actions">
                <x-staff.link to="staff.today" class="button button--quiet">Back to today’s shows</x-staff.link>
            </div>
        </section>
    @endif
@endsection
