@props(['rows'])

@php
    $statusLabels = ['open' => 'Open', 'closed' => 'Closed', 'not_open' => 'Not open'];
@endphp

<div class="staff-table-wrap">
    <table class="staff-table">
        <caption class="sr-only">Today's shows</caption>
        <thead>
            <tr>
                <th scope="col">Movie</th>
                <th scope="col">Screen</th>
                <th scope="col">Showtime</th>
                <th scope="col">Sold / scanned</th>
                <th scope="col">Entry window</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <th scope="row">{{ $row['movie'] }}</th>
                    <td>{{ $row['screen'] }}</td>
                    <td>{{ $row['time'] }}</td>
                    <td>{{ $row['sold'] }} / {{ $row['scanned'] }}</td>
                    <td>{{ $row['window'] }} · {{ $statusLabels[$row['status']] }}</td>
                    <td>
                        <x-staff.link
                            :to="'staff.'.$row['action']"
                            :params="['show' => $row['id']]"
                            class="staff-table__action"
                        >{{ ucfirst($row['action']) }} →</x-staff.link>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
