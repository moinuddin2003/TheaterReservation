@extends('emails.layout')

@section('preheader', 'Verify your CineBook email address to finish setting up your account.')
@section('eyebrow', 'Welcome to CineBook')
@section('headline', 'Let’s get you verified.')
@section('intro')
    Hi {{ $user->name }} — one quick step and you’ll be ready to find your next great movie.
@endsection

@section('content')
    <p style="margin:0 0 22px; color:#52627b;">
        Confirm that this is your email address so we can keep your bookings and tickets connected to your account.
    </p>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center" bgcolor="#3478f6" style="border-radius:8px;">
                <a href="{{ $url }}"
                    style="display:inline-block; padding:14px 22px; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none;">
                    Verify my email
                </a>
            </td>
        </tr>
    </table>
    <p style="margin:22px 0 6px; color:#73819a; font-size:12px;">If the button doesn’t work, copy this link into your browser:</p>
    <p style="margin:0; word-break:break-all; color:#3478f6; font-size:12px;">{{ $url }}</p>
    <p style="margin:20px 0 0; color:#73819a; font-size:12px;">If you didn’t create a CineBook account, you can safely ignore this email.</p>
@endsection
