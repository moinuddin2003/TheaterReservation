@props(['name', 'label', 'value' => null])

<x-admin.input :name="$name" :label="$label" type="date" :value="$value" {{ $attributes }} />
