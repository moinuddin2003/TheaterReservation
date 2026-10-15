@props([
    'title' => 'Admin',
    'breadcrumb' => 'Control room',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">
        <title>{{ $title }} · {{ config('app.name', 'CineBook') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div
            x-data="adminShell"
            @keydown.escape.window="mobileOpen = false; userMenuOpen = false"
            class="admin-shell"
            :data-theme="theme"
        >
            <div
                x-cloak
                x-show="mobileOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm lg:hidden"
            >
                <button
                    type="button"
                    class="absolute inset-0 h-full w-full cursor-default"
                    aria-label="Close navigation"
                    @click="mobileOpen = false"
                ></button>
            </div>

            <div class="flex min-h-screen">
                <x-admin.sidebar />

                <div class="min-w-0 flex-1">
                    <x-admin.topbar :title="$title" :breadcrumb="$breadcrumb" />
                    <main class="mx-auto w-full max-w-[1600px] px-4 py-7 sm:px-6 lg:px-10 lg:py-9">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
