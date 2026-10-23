@props(['title', 'date'])

<header class="staff-page__head">
    <p class="staff-page__eyebrow">Entry desk · {{ $date->format('D, M j, Y') }}</p>
    <h1>{{ $title }}</h1>
</header>
