@props(['label', 'for', 'focus' => false])

<div {{ $attributes->class(['staff-field', 'staff-field--focus' => $focus]) }}>
    <label class="staff-field__label" for="{{ $for }}">{{ $label }}</label>
    {{ $slot }}
</div>
