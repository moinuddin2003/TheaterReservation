@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $variant = match ($value) {
        'confirmed', 'succeeded', 'scheduled', 'valid', 'active', 'now_showing' => 'success',
        'pending', 'pending_payment', 'received', 'coming_soon', 'held' => 'warning',
        'failed', 'cancelled', 'expired', 'voided', 'archived', 'disabled' => 'danger',
        default => 'info',
    };
@endphp

<x-admin.badge :status="$variant">{{ str($value)->replace('_', ' ')->title() }}</x-admin.badge>
