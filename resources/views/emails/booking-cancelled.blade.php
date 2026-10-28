@extends('emails.layout')

@section('preheader', 'Your CineBook booking has been cancelled.')
@section('eyebrow', 'Booking update')
@section('headline', 'Your booking was cancelled.')
@section('intro', 'Hello '.$booking->user->name.' — booking '.$booking->reference.' has been cancelled. We’ve included the reservation details below for reference.')

@section('content')
    @include('emails.partials.booking-summary', ['booking' => $booking])
    <p style="margin:18px 0 0; color:#52627b;">
        If a refund was requested, its status will be updated separately. If you need help, reply to this email and include your booking reference.
    </p>
@endsection
