<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
    style="margin:22px 0; border:1px solid #e1e8f2; border-radius:12px; overflow:hidden;">
    <tr>
        <td style="padding:18px 20px; background:#f5f8fd;">
            <p style="margin:0 0 5px; color:#73819a; font-size:10px; font-weight:700; letter-spacing:1.4px; text-transform:uppercase;">
                {{ $booking->showtime->starts_at->copy()->timezone(config('cinema.timezone'))->format('D, M j · g:i A') }}
            </p>
            <h2 style="margin:0 0 9px; color:#172338; font-size:20px; line-height:1.3;">
                {{ $booking->showtime->movie->title }}
            </h2>
            <p style="margin:0; color:#52627b; font-size:13px;">
                {{ $booking->showtime->screen->cinema->name }}<br>
                {{ $booking->showtime->screen->name }}
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 20px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="padding:0 0 12px; color:#73819a; font-size:10px; font-weight:700; letter-spacing:1.3px; text-transform:uppercase;">
                        Your seats ({{ $booking->items->count() }})
                    </td>
                    @if ($showTickets ?? false)
                        <td align="right" style="padding:0 0 12px; color:#73819a; font-size:10px; font-weight:700; letter-spacing:1.3px; text-transform:uppercase;">
                            Ticket
                        </td>
                    @endif
                </tr>
                @foreach ($booking->items as $item)
                    <tr>
                        <td style="padding:10px 0; border-top:1px solid #edf1f6; color:#253650; font-size:13px;">
                            <strong>Seat {{ $item->seat_label }}</strong>
                            <span style="color:#73819a;"> · {{ ucfirst($item->seat_type->value) }}</span>
                            <br><span style="color:#73819a;">{{ strtoupper($booking->currency) }} {{ number_format($item->price_cents / 100, 2) }}</span>
                        </td>
                        @if ($showTickets ?? false)
                            <td align="right" style="padding:10px 0; border-top:1px solid #edf1f6; color:#253650; font-size:12px;">
                                {{ $item->ticket?->ticket_number ?? '—' }}
                            </td>
                        @endif
                    </tr>
                @endforeach
                <tr>
                    <td style="padding:14px 0 0; border-top:1px solid #dfe6f0; color:#52627b; font-size:13px;">
                        Booking reference<br><strong style="color:#172338;">{{ $booking->reference }}</strong>
                    </td>
                    <td align="right" style="padding:14px 0 0; border-top:1px solid #dfe6f0; color:#172338; font-size:14px; font-weight:700;">
                        {{ strtoupper($booking->currency) }} {{ number_format($booking->total_cents / 100, 2) }}
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:3px; color:#73819a; font-size:11px;">{{ $booking->items->count() }} {{ \Illuminate\Support\Str::plural('ticket', $booking->items->count()) }}</td>
                    <td align="right" style="padding-top:3px; color:#73819a; font-size:11px;">Total</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
