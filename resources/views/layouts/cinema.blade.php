<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark light">
    <title>@yield('title', $cinema?->name ?? config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
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
                    @if (auth()->user()->isCustomer())
                        <a class="site-nav__link {{ request()->routeIs('bookings.*') ? 'is-active' : '' }}"
                            href="{{ route('bookings.index') }}">My bookings</a>
                    @endif
                @else
                    <a class="site-nav__link" href="{{ route('login') }}">My bookings</a>
                @endauth
            </nav>

            <div class="site-header__actions">
                <span class="location-label"><span aria-hidden="true">⌖</span> {{ $cinema?->name ?? config('app.name') }}</span>
                <x-theme-toggle />
                @auth
                    <div class="site-account-menu" x-data="{ open: false }" @click.outside="open = false"
                        @keydown.escape.window="open = false">
                        <button class="profile-label site-account-menu__trigger" type="button" aria-haspopup="menu"
                            :aria-expanded="open.toString()" @click="open = ! open">
                            <span class="profile-avatar">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                            <span class="site-account-menu__name">{{ auth()->user()->name }}</span>
                            <svg class="site-account-menu__chevron" :class="{ 'is-open': open }" aria-hidden="true"
                                viewBox="0 0 16 16" fill="none">
                                <path d="m4 6 4 4 4-4" stroke="currentColor" stroke-linecap="round"
                                    stroke-linejoin="round" stroke-width="1.5" />
                            </svg>
                        </button>
                        <div class="site-account-menu__dropdown" x-cloak x-show="open" x-transition
                            role="menu" aria-label="Account menu">
                            <a class="site-account-menu__item" href="{{ route('profile.edit') }}" role="menuitem">
                                Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="site-account-menu__item" type="submit" role="menuitem">
                                    Log out
                                </button>
                            </form>
                        </div>
                    </div>
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
