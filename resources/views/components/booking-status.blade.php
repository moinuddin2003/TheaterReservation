@props(['status'])

@php($statusValue = $status->value)
@php($statusLabel = match ($statusValue) {
    'pending_payment' => 'Payment needed',
    'failed' => 'Payment failed',
    'expired' => 'Expired',
    default => ucwords(str_replace('_', ' ', $statusValue)),
})

<span class="booking-status booking-status--{{ str_replace('_', '-', $statusValue) }}">
    {{ $statusLabel }}
</span>
