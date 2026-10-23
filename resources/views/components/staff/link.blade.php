@props(['to', 'params' => []])

{{-- Falls back to "#" until the target staff screen has its route. --}}
<a href="{{ Route::has($to) ? route($to, $params) : '#' }}" {{ $attributes }}>{{ $slot }}</a>
