<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark light">
    <title>@yield('title', 'Staff') · {{ config('cinema.brand') }}</title>
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="cinema-app staff-app">
    <x-staff.nav :user="auth()->user()->name" :active="trim($__env->yieldContent('active', 'today'))" />

    <main class="staff-page">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
