@extends('emails.layout')

@section('preheader', 'Use the secure link to reset your CineBook password.')
@section('eyebrow', 'Account security')
@section('headline', 'Choose a new password.')
@section('intro')
    Hi {{ $user->name }} — we received a request to reset the password for your CineBook account.
@endsection

@section('content')
    <p style="margin:0 0 22px; color:#52627b;">
        This secure link expires in {{ $expires }} minutes. If you didn’t request a reset, no action is needed and your password will stay the same.
    </p>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center" bgcolor="#3478f6" style="border-radius:8px;">
                <a href="{{ $url }}"
                    style="display:inline-block; padding:14px 22px; color:#ffffff; font-size:14px; font-weight:700; text-decoration:none;">
                    Reset my password
                </a>
            </td>
        </tr>
    </table>
    <p style="margin:22px 0 6px; color:#73819a; font-size:12px;">If the button doesn’t work, copy this link into your browser:</p>
    <p style="margin:0; word-break:break-all; color:#3478f6; font-size:12px;">{{ $url }}</p>
    <p style="margin:20px 0 0; color:#73819a; font-size:12px;">For your security, don’t share this link with anyone.</p>
@endsection
