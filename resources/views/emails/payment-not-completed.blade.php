@extends('emails.layout')

@section('preheader', 'Your payment was not completed and your held seats have been released.')
@section('eyebrow', 'Payment update')
@section('headline', 'Your payment didn’t go through.')
@section('intro', 'Hello '.$booking->user->name.' — we couldn’t complete the payment, so your seat hold has expired and the seats are no longer reserved.')

@section('content')
    @include('emails.partials.booking-summary', ['booking' => $booking])
    <p style="margin:18px 0 0; color:#52627b;">
        If you still want to attend, choose available seats and start a new booking. If your account was charged after the hold expired, we’ll automatically request a refund.
    </p>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-top:24px;">
        <tr>
            <td align="center" bgcolor="#3478f6" style="border-radius:8px;">
                <a href="{{ route('movies.index') }}"
                    style="display:inline-block; padding:13px 20px; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none;">
                    Browse movies and showtimes
                </a>
            </td>
        </tr>
    </table>
@endsection
