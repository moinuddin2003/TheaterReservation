@props(['row', 'number', 'type', 'status'])

@php
    $label = $row.$number;
    $isUnavailable = in_array($status, ['held', 'sold', 'disabled'], true);
@endphp

<button
    type="button"
    class="seat {{ $type === 'premium' ? 'seat--premium' : '' }} seat--{{ $status }}"
    aria-label="Row {{ $row }}, seat {{ $number }}, {{ $status }}"
    aria-pressed="{{ $status === 'selected' ? 'true' : 'false' }}"
    data-seat="{{ $label }}"
    data-seat-label="{{ $label }}"
    data-seat-kind="{{ $type }}"
    data-seat-type-label="{{ ucfirst($type) }}"
    @disabled($isUnavailable)
>{{ sprintf('%02d', $number) }}</button>
