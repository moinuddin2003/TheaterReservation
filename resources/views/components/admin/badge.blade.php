@props(['status' => 'info'])

@php
    $styles = match ($status) {
        'success' => 'border-cine-success/20 bg-cine-success/10 text-cine-success',
        'warning' => 'border-cine-warning/20 bg-cine-warning/10 text-cine-warning',
        'danger' => 'border-cine-danger/20 bg-cine-danger/10 text-cine-danger',
        default => 'border-cine-accent/20 bg-cine-accent/10 text-cine-accent',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold', $styles]) }}>
    {{ $slot }}
</span>
