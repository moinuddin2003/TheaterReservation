@props(['name', 'label', 'value' => null, 'required' => false, 'rows' => 4])

@php($fieldName = str($name)->replace(['[', ']'], ['.', ''])->trim('.')->toString())

<label class="block text-xs font-semibold text-cine-secondary" for="{{ $name }}">
    {{ $label }}
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) @error($fieldName) aria-invalid="true" aria-describedby="{{ $fieldName }}-error" @enderror {{ $attributes->class(['mt-1.5 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary', 'border-cine-danger' => $errors->has($fieldName)]) }}>{{ old($fieldName, $value) }}</textarea>
    @error($fieldName)<span id="{{ $fieldName }}-error" class="mt-1 block text-xs text-cine-danger">{{ $message }}</span>@enderror
</label>
