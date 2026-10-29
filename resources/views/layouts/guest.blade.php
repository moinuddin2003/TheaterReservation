@props([
    'title' => 'Your next great escape',
    'subtitle' => 'Secure account access for your next movie night.',
    'compact' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark light">
    <title>{{ $title }} — {{ $cinema?->name ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    <x-theme-init />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-page">
    <main @class(['auth-layout', 'auth-layout--compact' => $compact])>
        <section class="auth-story" aria-label="CineBook">
            <a class="brand auth-story__brand" href="{{ route('home') }}">
                <span class="brand__mark" aria-hidden="true"></span>
                <span>{{ $cinema?->name ?? config('app.name') }}</span>
            </a>
            @php
                $featuredSlug = config('cinema.featured_movie');
                $posterPath = config("cinema.movies.{$featuredSlug}.images.poster");
                $movieTitle = config("cinema.movies.{$featuredSlug}.title");
            @endphp

            <x-movie-image :src="$posterPath" :alt="$movieTitle . ' poster'" class="auth-story__image" />
            <div class="auth-story__copy">
                <p class="eyebrow">YOUR NEXT GREAT ESCAPE</p>
                <h2>The big screen. Your kind of story.</h2>
                <p>One cinema. Extraordinary worlds. Make your next night a movie night.</p>
            </div>
        </section>

        <section class="auth-main">
            <div class="auth-main__inner">
                <a class="back-link" href="{{ route('home') }}"><span aria-hidden="true">←</span> Back to movies</a>
                <h1>{{ $title }}</h1>
                <p class="auth-subtitle">{{ $subtitle }}</p>
                <div class="auth-content">{{ $slot }}</div>
                <p class="auth-footer">{{ $cinema?->name ?? config('app.name') }} · Secure account access. Reservations use Stripe
                    hosted Checkout in test mode.</p>
            </div>
        </section>
    </main>
    <x-theme-toggle floating />
</body>

</html>