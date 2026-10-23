@props(['tickets'])

<div class="staff-table-wrap lookup-tickets">
    <table class="staff-table lookup-tickets__table">
        <caption class="sr-only">Tickets in this booking</caption>
        <thead>
            <tr>
                <th scope="col">Seat</th>
                <th scope="col">Ticket</th>
                <th scope="col">Admission status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tickets as $ticket)
                <tr>
                    <th scope="row">{{ $ticket['seat'] }}</th>
                    <td data-label="Ticket">{{ $ticket['code'] }}</td>
                    <td data-label="Admission status">{{ $ticket['admission'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
