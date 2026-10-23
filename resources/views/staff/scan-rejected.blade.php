@extends('layouts.staff')

@section('title', 'Ticket not admitted')
@section('active', 'scan')

@section('content')
    <x-staff.page-head title="No ambiguity at the entrance." :date="$now" />

    <div class="scan-layout">
        <div class="scan-layout__main">
            <x-staff.scan-rejection :rejection="$rejection" />

            @if ($scan['selected'])
                <x-staff.next-ticket-form :show="$scan['selected']" :lookup="true" />

                <h2 class="scan-layout__heading">Recent 10 attempts</h2>
                <x-staff.attempts-table :attempts="$scan['attempts']" />
            @endif
        </div>

        @if ($scan['selected'])
            <aside class="scan-layout__side">
                <x-staff.show-status :shows="$scan['shows']" :selected="$scan['selected']" :now="$now" />
            </aside>
        @endif
    </div>

    @if ($scan['selected'])
        @push('scripts')
            <x-staff.scan-focus-script />
        @endpush
    @endif
@endsection
