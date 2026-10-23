@props(['row', 'number', 'type', 'status'])

@php
    $label = $row.$number;
    $isUnavailable = in_array($status, ['held', 'sold', 'disabled'], true);
@endphp

<button
    type="button"
    class="seat {{ $type === 'premium' ? 'seat--premium' : '' }} seat--{{ $status }}"
    aria-label="{{ $label }} {{ $status }}"
    aria-pressed="{{ $status === 'selected' ? 'true' : 'false' }}"
    data-seat="{{ $label }}"
    data-seat-type="{{ $type }}"
    @disabled($isUnavailable)
>{{ $number }}</button>
