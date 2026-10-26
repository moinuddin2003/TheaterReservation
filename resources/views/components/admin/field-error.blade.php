@props(['field', 'bag' => 'default'])

@php
    $errorBag = $errors->getBag($bag);
    $message = $errorBag->first($field);

    if (! $message && str_ends_with($field, '.*')) {
        $parentField = substr($field, 0, -2);
        $message = $errorBag->first($parentField);

        if (! $message) {
            foreach ($errorBag->getMessages() as $key => $messages) {
                if (str_starts_with($key, $parentField.'.')) {
                    $message = $messages[0] ?? null;
                    break;
                }
            }
        }
    }
@endphp

@if ($message)
    <p {{ $attributes->merge(['class' => 'mt-1 text-xs text-cine-danger']) }} role="alert">{{ $message }}</p>
@endif
