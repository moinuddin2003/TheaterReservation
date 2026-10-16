@props(['name', 'label', 'value' => null, 'required' => false, 'rows' => 4])

<label class="block text-xs font-semibold text-cine-secondary" for="{{ $name }}">
    {{ $label }}
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) @error($name) aria-invalid="true" @enderror {{ $attributes->class(['mt-1.5 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary']) }}>{{ old($name, $value) }}</textarea>
    @error($name)<span class="mt-1 block text-xs text-cine-danger">{{ $message }}</span>@enderror
</label>
