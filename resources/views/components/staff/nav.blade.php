@props(['user', 'active' => 'today'])

@php
    $links = [
        'today' => ['Today', 'staff.today'],
        'scan' => ['Scan', 'staff.scan'],
        'roster' => ['Roster', 'staff.roster'],
        'lookup' => ['Reference lookup', 'staff.lookup'],
    ];
@endphp

<header class="staff-header">
    <div class="staff-header__inner">
        <a class="brand" href="{{ route('staff.today') }}" aria-label="{{ config('cinema.brand') }} staff home">
            <span class="brand__mark" aria-hidden="true"></span>
            <span>{{ config('cinema.brand') }}</span>
        </a>
        <span class="staff-header__badge">• STAFF</span>

        <nav class="staff-nav" aria-label="Staff navigation">
            @foreach ($links as $key => [$label, $routeName])
                <x-staff.link
                    :to="$routeName"
                    :class="'staff-nav__link'.($active === $key ? ' is-active' : '')"
                    :aria-current="$active === $key ? 'page' : null"
                >{{ $label }}</x-staff.link>
            @endforeach
        </nav>

        <div class="staff-header__account">
            <span>{{ $user }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="staff-header__signout" type="submit">Sign out</button>
            </form>
            <x-theme-toggle />
        </div>
    </div>
</header>
