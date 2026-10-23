@props(['status'])

@php($statusValue = $status->value)

<span class="booking-status booking-status--{{ str_replace('_', '-', $statusValue) }}">
    {{ ucwords(str_replace('_', ' ', $statusValue)) }}
</span>
