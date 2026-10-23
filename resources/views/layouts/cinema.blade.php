<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark light">
    <title>@yield('title', $cinema?->name ?? config('app.name'))</title>
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="cinema-app">
    <header class="site-header">
        <div class="site-header__inner">
            <a class="brand" href="{{ route('home') }}" aria-label="{{ $cinema?->name ?? config('app.name') }} home">
                <span class="brand__mark" aria-hidden="true"></span>
                <span>{{ $cinema?->name ?? config('app.name') }}</span>
            </a>

            <nav class="site-nav" aria-label="Main navigation">
                <a class="site-nav__link {{ request()->routeIs('home') ? 'is-active' : '' }}"
                    href="{{ route('home') }}">Home</a>
                <a class="site-nav__link {{ request()->routeIs('movies.*') ? 'is-active' : '' }}"
                    href="{{ route('movies.index') }}">Movies</a>
                @auth
                    <a class="site-nav__link" href="{{ route('dashboard') }}">My bookings</a>
                @else
                    <a class="site-nav__link" href="{{ route('login') }}">My bookings</a>
                @endauth
            </nav>

            <div class="site-header__actions">
                <span class="location-label"><span aria-hidden="true">⌖</span> {{ $cinema?->name ?? config('app.name') }}</span>
                <x-theme-toggle />
                @auth
                    <span class="profile-label">                    <span class="profile-avatar">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                    {{ auth()->user()->name }}
                </span>
                @else
                    <a class="profile-label" href="{{ route('login') }}">Sign in</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="site-main">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="site-footer__inner">
            <a class="brand brand--footer" href="{{ route('home') }}">
                <span class="brand__mark" aria-hidden="true"></span>
                <span>{{ $cinema?->name ?? config('app.name') }}</span>
            </a>
            <p>{{ $cinema?->name ?? config('app.name') }} <span aria-hidden="true">·</span> One cinema. Every great escape.</p>
            <p>USD <span aria-hidden="true">·</span> Stripe test checkout <span aria-hidden="true">·</span> No live charges</p>
        </div>
    </footer>

    <div class="toast" role="status" aria-live="polite" data-toast></div>
</body>

</html>
