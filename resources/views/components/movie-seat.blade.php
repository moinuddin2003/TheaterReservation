@props(['row', 'number', 'type', 'status', 'seatId'])

<button
    type="button"
    data-seat
    data-seat-id="{{ $seatId }}"
    data-seat-label="{{ $row.$number }}"
    data-seat-kind="{{ $type }}"
    data-seat-type-label="{{ ucfirst($type) }}"
    aria-label="Row {{ $row }}, seat {{ $number }}, {{ $status }}"
    aria-pressed="{{ $status === 'selected' ? 'true' : 'false' }}"
    data-seat="{{ $label }}"
    data-seat-label="{{ $label }}"
    data-seat-kind="{{ $type }}"
    data-seat-type-label="{{ ucfirst($type) }}"
    @disabled($isUnavailable)
>{{ sprintf('%02d', $number) }}</button>
