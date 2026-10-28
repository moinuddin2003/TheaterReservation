@extends('emails.layout')

@section('preheader', 'Your CineBook booking is confirmed. Your seats and tickets are ready.')
@section('eyebrow', 'Booking confirmed')
@section('headline', 'You’re all set for movie night.')
@section('intro', 'Hello '.$booking->user->name.' — your payment is confirmed. Here are all the details for your visit.')

@section('content')
    <p style="margin:0 0 8px; color:#168b69; font-size:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase;">
        Payment received
    </p>
    @include('emails.partials.booking-summary', ['booking' => $booking, 'showTickets' => true])
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-top:24px;">
        <tr>
            <td align="center" bgcolor="#3478f6" style="border-radius:8px;">
                <a href="{{ route('bookings.show', $booking->reference) }}"
                    style="display:inline-block; padding:13px 20px; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none;">
                    View booking and tickets
                </a>
            </td>
        </tr>
    </table>
    <p style="margin:18px 0 0; color:#73819a; font-size:12px;">
        Keep your booking reference handy and arrive a little early. Enjoy the show!
    </p>
@endsection
