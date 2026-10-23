@extends('layouts.staff')

@section('title', 'Reference lookup')
@section('active', 'lookup')

@section('content')
    <x-staff.page-head title="Help a guest find their ticket" :date="$now" />

    <x-staff.lookup-form :reference="$lookup['reference']" />

    @if ($lookup['state'] === 'found')
        <div class="lookup-layout">
            <x-staff.lookup-booking :booking="$lookup['booking']" :venue="$venue" />
            <x-staff.lookup-context :booking="$lookup['booking']" />
        </div>
    @else
        <x-staff.lookup-empty :state="$lookup['state']" :reference="$lookup['reference']" />
    @endif
@endsection
