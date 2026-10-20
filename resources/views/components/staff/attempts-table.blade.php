@props(['attempts'])

@php
    $labels = [
        'admitted' => 'Admitted',
        'already_used' => 'Already used',
        'wrong_showtime' => 'Wrong showtime',
        'too_early' => 'Too early',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled / refunded',
        'void' => 'Cancelled / refunded',
        'invalid' => 'Invalid',
    ];
@endphp

<div class="staff-table-wrap">
    <table class="staff-table staff-table--compact staff-table--attempts">
        <caption class="sr-only">Recent scan attempts</caption>
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Result</th>
                <th scope="col">Seat</th>
                <th scope="col">Ticket</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attempts as $attempt)
                <tr>
                    <th scope="row">{{ $attempt['time'] }}</th>
                    <td>{{ $labels[$attempt['result']] ?? $attempt['result'] }}@if (! empty($attempt['used_at'])) · {{ $attempt['used_at'] }}@endif</td>
                    <td>{{ $attempt['seat'] ?? '—' }}</td>
                    <td>{{ $attempt['ticket'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No scan attempts yet for this show.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
