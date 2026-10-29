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
            <div class="site-account-menu" x-data="{ open: false }" @click.outside="open = false"
                @keydown.escape.window="open = false">
                <button class="profile-label site-account-menu__trigger" type="button" aria-haspopup="menu"
                    :aria-expanded="open.toString()" @click="open = ! open">
                    <span class="profile-avatar">{{ str($user)->substr(0, 1)->upper() }}</span>
                    <span class="site-account-menu__name">{{ $user }}</span>
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
            <x-theme-toggle />
        </div>
    </div>
</header>
