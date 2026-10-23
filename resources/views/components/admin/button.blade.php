@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $styles = match ($variant) {
        'secondary', 'outline' => 'border border-cine-border/10 bg-cine-surface text-cine-secondary hover:border-cine-accent/40 hover:bg-cine-elevated hover:text-cine-primary',
        'danger' => 'border border-cine-danger/20 bg-cine-danger/10 text-cine-danger hover:bg-cine-danger/20',
        default => 'border border-cine-accent/50 bg-cine-accent text-white shadow-cine-glow hover:bg-cine-accent/90',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['inline-flex min-h-10 items-center justify-center gap-2 rounded-full px-5 py-2 text-sm font-semibold transition-colors duration-150 motion-reduce:transition-none', $styles]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['inline-flex min-h-10 items-center justify-center gap-2 rounded-full px-5 py-2 text-sm font-semibold transition-colors duration-150 motion-reduce:transition-none', $styles]) }}>
        {{ $slot }}
    </button>
@endif
