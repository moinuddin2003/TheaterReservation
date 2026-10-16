@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false])

<label class="block text-xs font-semibold text-cine-secondary" for="{{ $name }}">
    {{ $label }}
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required) @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror {{ $attributes->class(['mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary placeholder:text-cine-tertiary', 'border-cine-danger' => $errors->has($name)]) }}>
    @error($name)<span id="{{ $name }}-error" class="mt-1 block text-xs text-cine-danger">{{ $message }}</span>@enderror
</label>
