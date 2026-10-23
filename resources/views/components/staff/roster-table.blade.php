@props(['rows', 'query' => ''])

{{-- On small screens each row turns into a card; the data-label text is shown before each value. --}}
<div class="staff-table-wrap roster-table-wrap">
    <table class="staff-table roster-table">
        <caption class="sr-only">Confirmed bookings for this show</caption>
        <thead>
            <tr>
                <th scope="col">Booking reference</th>
                <th scope="col">Customer name</th>
                <th scope="col">Seats</th>
                <th scope="col">Seat count</th>
                <th scope="col">Scanned count</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <th scope="row">{{ $row['ref'] }}</th>
                    <td data-label="Customer name">{{ $row['name'] }}</td>
                    <td data-label="Seats">{{ $row['seats'] }}</td>
                    <td data-label="Seat count">{{ $row['seat_count'] }}</td>
                    <td data-label="Scanned count">{{ $row['scanned'] }}</td>
                </tr>
            @empty
                <tr class="roster-table__empty">
                    <td colspan="5">{{ $query !== '' ? 'No bookings match “'.$query.'”.' : 'No confirmed bookings for this show yet.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
