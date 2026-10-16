@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false])

@php($fieldName = str($name)->replace(['[', ']'], ['.', ''])->trim('.')->toString())

<label class="block text-xs font-semibold text-cine-secondary" for="{{ $name }}">
    {{ $label }}
    <select id="{{ $name }}" name="{{ $name }}" @required($required) @error($fieldName) aria-invalid="true" aria-describedby="{{ $fieldName }}-error" @enderror {{ $attributes->class(['mt-1.5 min-h-10 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary', 'border-cine-danger' => $errors->has($fieldName)]) }}>
        {{ $slot }}
        @foreach ($options as $key => $option)
            <option value="{{ $key }}" @selected(old($fieldName, $value) == $key)>{{ $option }}</option>
        @endforeach
    </select>
    @error($fieldName)<span id="{{ $fieldName }}-error" class="mt-1 block text-xs text-cine-danger">{{ $message }}</span>@enderror
</label>
