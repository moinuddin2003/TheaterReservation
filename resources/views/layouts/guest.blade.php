@props([
    'title' => 'Admin sign in',
    'subtitle' => 'Sign in to manage CineBook.',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <main>
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
        {{ $slot }}
    </main>
</body>

</html>
