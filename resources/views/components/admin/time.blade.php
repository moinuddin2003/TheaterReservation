@props(['name', 'label', 'value' => null])

<x-admin.input :name="$name" :label="$label" type="datetime-local" :value="$value" {{ $attributes }} />
