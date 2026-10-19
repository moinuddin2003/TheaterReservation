@props(['row', 'number', 'type', 'status'])

<button
    class="seat seat--{{ $status }} {{ $type === 'premium' ? 'seat--premium' : '' }}"
    type="button"
    data-seat
    data-seat-label="{{ $row.$number }}"
    data-seat-kind="{{ $type }}"
    data-seat-type-label="{{ ucfirst($type) }}"
    aria-label="Row {{ $row }}, seat {{ $number }}, {{ $status }}"
    aria-pressed="{{ $status === 'selected' ? 'true' : 'false' }}"
    @disabled(in_array($status, ['held', 'sold', 'disabled'], true))
>{{ sprintf('%02d', $number) }}</button>
