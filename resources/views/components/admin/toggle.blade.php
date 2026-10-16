@props(['name', 'label', 'checked' => false])

<label class="flex items-center gap-3 text-sm text-cine-secondary">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked)) {{ $attributes->class(['rounded border-cine-border/20 bg-cine-bg text-cine-accent focus:ring-cine-accent']) }}>
    <span>{{ $label }}</span>
</label>
