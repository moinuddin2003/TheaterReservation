@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false])

@php($fieldName = str($name)->replace(['[', ']'], ['.', ''])->trim('.')->toString())

<label class="block text-xs font-semibold text-cine-secondary" for="{{ $name }}">
    {{ $label }}
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($fieldName, $value) }}" @required($required) @error($fieldName) aria-invalid="true" aria-describedby="{{ $fieldName }}-error" @enderror {{ $attributes->class(['mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary placeholder:text-cine-tertiary', 'border-cine-danger' => $errors->has($fieldName)]) }}>
    @error($fieldName)<span id="{{ $fieldName }}-error" class="mt-1 block text-xs text-cine-danger">{{ $message }}</span>@enderror
</label>
