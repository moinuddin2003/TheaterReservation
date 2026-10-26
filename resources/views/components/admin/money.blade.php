@props([
    'cents',
    'currency' => 'USD',
])

@php
    $currencyCode = strtoupper($currency);
    $symbol = match ($currencyCode) {
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'INR' => '₹',
        'PKR' => 'Rs. ',
        'CAD' => 'CA$',
        'AUD' => 'A$',
        default => $currencyCode.' ',
    };
@endphp

{{ $symbol }}{{ number_format($cents / 100, 2) }}
