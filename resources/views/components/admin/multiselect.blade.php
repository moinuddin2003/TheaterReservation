@props(['name', 'label', 'options', 'selected' => []])

<fieldset class="block text-xs font-semibold text-cine-secondary">
    <legend>{{ $label }}</legend>
    <select id="{{ $name }}" name="{{ $name }}[]" multiple {{ $attributes->class(['mt-1.5 min-h-28 w-full rounded-xl border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary']) }}>
        @foreach ($options as $option)
            <option value="{{ $option->id }}" @selected(in_array($option->id, old($name, $selected), false))>{{ $option->name }}</option>
        @endforeach
    </select>
    @error($name)<span class="mt-1 block text-xs text-cine-danger">{{ $message }}</span>@enderror
</fieldset>
