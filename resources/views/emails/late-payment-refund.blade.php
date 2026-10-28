@extends('emails.layout')

@section('preheader', 'An update about your late payment and refund.')
@section('eyebrow', 'Refund update')
@section('headline', 'An update on your payment.')
@section('intro', 'Hello '.$refund->booking->user->name.' — we received your payment after the seat hold expired, so the booking could not be confirmed.')

@section('content')
    @if ($refund->isSucceeded())
        <p style="margin:0 0 8px; color:#168b69; font-size:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase;">Refund confirmed</p>
        <p style="margin:0; color:#34445f;">
            Stripe confirmed your refund of <strong>{{ strtoupper($refund->currency) }} {{ number_format($refund->amount_cents / 100, 2) }}</strong>.
            Your bank may take several business days to show the funds.
        </p>
    @elseif ($refund->isFailed())
        <p style="margin:0 0 8px; color:#b54747; font-size:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase;">Refund needs attention</p>
        <p style="margin:0; color:#34445f;">
            We couldn’t complete your refund of <strong>{{ strtoupper($refund->currency) }} {{ number_format($refund->amount_cents / 100, 2) }}</strong>.
            Please contact us with your booking reference so we can help.
        </p>
    @else
        <p style="margin:0 0 8px; color:#3478f6; font-size:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase;">Refund processing</p>
        <p style="margin:0; color:#34445f;">
            We’ve requested a refund of <strong>{{ strtoupper($refund->currency) }} {{ number_format($refund->amount_cents / 100, 2) }}</strong>.
            Your bank may take several business days to show the funds.
        </p>
    @endif
    @include('emails.partials.booking-summary', ['booking' => $refund->booking])
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-top:24px;">
        <tr>
            <td align="center" bgcolor="#3478f6" style="border-radius:8px;">
                <a href="{{ route('bookings.show', $refund->booking->reference) }}"
                    style="display:inline-block; padding:13px 20px; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none;">
                    View booking details
                </a>
            </td>
        </tr>
    </table>
@endsection
