@extends('layouts.staff')

@section('title', 'Ticket admitted')
@section('active', 'scan')

@section('content')
    <x-staff.page-head title="Welcome to the show." :date="$now" />

    <div class="scan-layout">
        <div class="scan-layout__main">
            <x-staff.scan-result :result="$result" />
            <x-staff.next-ticket-form :show="$scan['selected']" />

            <h2 class="scan-layout__heading">Recent 10 attempts</h2>
            <x-staff.attempts-table :attempts="$scan['attempts']" />
        </div>

        <aside class="scan-layout__side">
            <x-staff.show-status :shows="$scan['shows']" :selected="$scan['selected']" :now="$now" />
        </aside>
    </div>

    @push('scripts')
        <x-staff.scan-focus-script />
    @endpush
@endsection
