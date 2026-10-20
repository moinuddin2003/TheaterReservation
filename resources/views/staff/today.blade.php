@extends('layouts.staff')

@section('title', "Today's shows")

@section('content')
    <x-staff.page-head :title="'Today at '.$venue" :date="$now" />

    <div class="staff-stats">
        <x-staff.stat-card label="Scheduled shows" :value="$overview['summary']['scheduled']" />
        <x-staff.stat-card label="Tickets expected" :value="$overview['summary']['expected']" />
        <x-staff.stat-card label="Guests admitted" :value="$overview['summary']['admitted']" />
    </div>

    @if ($overview['nearest'])
        <x-staff.nearest-show :show="$overview['nearest']" :now="$now" />
    @endif

    <x-staff.shows-table :rows="$overview['rows']" />

    <x-staff.notice>
        Use a USB keyboard scanner or enter the ticket token manually. For damaged QR codes, look up the booking reference. No camera is required.
    </x-staff.notice>
@endsection
