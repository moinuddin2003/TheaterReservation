@props(['name', 'class' => 'h-5 w-5'])

<svg {{ $attributes->class([$class, 'shrink-0']) }} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('layout-dashboard')
            <rect x="3" y="3" width="7" height="9" rx="1.5" /><rect x="14" y="3" width="7" height="5" rx="1.5" /><rect x="14" y="12" width="7" height="9" rx="1.5" /><rect x="3" y="16" width="7" height="5" rx="1.5" />
            @break
        @case('clapperboard')
            <path d="M4 4h16v16H4z" /><path d="m4 9 16-5M8 4l5 5M15 4l5 5M4 15h16" />
            @break
        @case('tags')
            <path d="m20.6 13.4-7.2 7.2a2 2 0 0 1-2.8 0l-7.2-7.2a2 2 0 0 1 0-2.8l7.2-7.2a2 2 0 0 1 1.4-.6H19a2 2 0 0 1 2 2v7.2a2 2 0 0 1-.4 1.4Z" /><circle cx="16.5" cy="7.5" r=".5" />
            @break
        @case('armchair')
            <path d="M19 9V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v4" /><path d="M3 11v3a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3a2 2 0 0 0-4 0v1H7v-1a2 2 0 0 0-4 0Z" /><path d="M5 16v5M19 16v5M8 16v2h8v-2" />
            @break
        @case('building')
            <rect x="4" y="2" width="16" height="20" rx="2" /><path d="M9 22v-4h6v4M8 6h1M15 6h1M8 10h1M15 10h1M8 14h1M15 14h1" />
            @break
        @case('cinema')
            <path d="M3 21h18M5 21V8l7-5 7 5v13M8 21v-8h8v8M8 9h.01M12 9h.01M16 9h.01" />
            @break
        @case('filter')
            <path d="M4 5h16l-6.5 7.5v5l-3 1.5v-6.5L4 5Z" />
            @break
        @case('monitor')
            <rect x="3" y="4" width="18" height="13" rx="2" /><path d="M8 21h8M12 17v4" />
            @break
        @case('star')
            <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z" />
            @break
        @case('check-circle')
            <path d="M20 11.1V12a8 8 0 1 1-4.7-7.3" /><path d="m9 11 3 3L22 4" />
            @break
        @case('circle-check')
            <circle cx="12" cy="12" r="9" /><path d="m8 12 2.5 2.5L16 9" />
            @break
        @case('chart-column')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" />
            @break
        @case('calendar-clock')
            <path d="M8 2v4M16 2v4M3 10h11M5 4h14a2 2 0 0 1 2 2v6" /><rect x="3" y="4" width="18" height="17" rx="2" /><circle cx="18" cy="18" r="3" /><path d="M18 16.5V18l1 1" />
            @break
        @case('calendar')
            <rect x="3" y="4" width="18" height="17" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" />
            @break
        @case('play')
            <circle cx="12" cy="12" r="10" /><path d="m10 8 6 4-6 4z" />
            @break
        @case('film-reel')
            <circle cx="12" cy="12" r="10" /><circle cx="12" cy="12" r="2" /><circle cx="12" cy="6" r="1.5" /><circle cx="17.2" cy="9" r="1.5" /><circle cx="17.2" cy="15" r="1.5" /><circle cx="12" cy="18" r="1.5" /><circle cx="6.8" cy="15" r="1.5" /><circle cx="6.8" cy="9" r="1.5" />
            @break
        @case('ticket')
            <path d="M2 9a3 3 0 0 0 0 6v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a3 3 0 0 1 0-6V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" /><path d="M13 5v2M13 11v2M13 17v2" />
            @break
        @case('ticket-x')
            <path d="M2 9a3 3 0 0 0 0 6v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a3 3 0 0 1 0-6V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" /><path d="M13 5v2M13 11v2M13 17v2M9 10l4 4m0-4-4 4" />
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" />
            @break
        @case('ban')
            <circle cx="12" cy="12" r="9" /><path d="m5.6 5.6 12.8 12.8" />
            @break
        @case('credit-card')
            <rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20M6 15h2" />
            @break
        @case('undo-2')
            <path d="M9 14 4 9l5-5" /><path d="M4 9h10a6 6 0 0 1 0 12h-1" />
            @break
        @case('scan-line')
            <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M3 12h18" />
            @break
        @case('lock')
            <rect x="4" y="10" width="16" height="11" rx="2" /><path d="M8 10V7a4 4 0 1 1 8 0v3M12 14v3" />
            @break
        @case('users')
            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="10" cy="7" r="4" /><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
            @break
        @case('shield-check')
            <path d="M12 3 5 6v5c0 4.5 2.8 8.2 7 10 4.2-1.8 7-5.5 7-10V6l-7-3Z" /><path d="m9 12 2 2 4-4" />
            @break
        @case('settings')
            <path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" /><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.5.9l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.5-.9l-1.7.7-1.4-2.4 1.4-1.1a7 7 0 0 1 0-1.8l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.5-.9l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.5.9l1.7-.7 1.4 2.4-1.4 1.1a7 7 0 0 1 0 1.8Z" transform="translate(-1 -1)" />
            @break
        @case('webhook')
            <path d="M18 16.5a3.5 3.5 0 1 0 3.2 5H22v-2h-.8a3.5 3.5 0 0 0-3.2-3Z" /><path d="M6 7.5a3.5 3.5 0 1 0-3.2-5H2v2h.8a3.5 3.5 0 0 0 3.2 3Z" /><path d="M6 7.5 9 12l-3 4.5M18 16.5 15 12l3-4.5M6 16.5h12M6 7.5h12" /><circle cx="12" cy="12" r="2" />
            @break
        @case('scroll-text')
            <path d="M8 21h12a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H8" /><path d="M6 18a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h2v18H6ZM12 8h6M12 12h6M12 16h4" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" />
            @break
        @case('bell')
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
            @break
        @case('sun')
            <circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
            @break
        @case('moon')
            <path d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z" />
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('arrow-left')
            <path d="m12 19-7-7 7-7M5 12h14" />
            @break
        @case('arrow-right')
            <path d="M5 12h14M12 5l7 7-7 7" />
            @break
        @case('chart-no-axes-column-increasing')
            <path d="M3 3v18h18M8 16v-4M13 16V8M18 16V5" />
            @break
        @case('info')
            <circle cx="12" cy="12" r="10" /><path d="M12 11v5M12 8h.01" />
            @break
        @case('download')
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
            @break
        @case('more-vertical')
            <circle cx="12" cy="5" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="12" cy="19" r="1" />
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6" />
            @break
        @case('chevron-left')
            <path d="m15 18-6-6 6-6" />
            @break
        @case('chevron-right')
            <path d="m9 18 6-6-6-6" />
            @break
        @case('log-out')
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" />
            @break
        @case('film')
            <rect x="3" y="3" width="18" height="18" rx="2" /><path d="M7 3v18M3 7h4M3 12h18M3 17h4M17 3v18M17 7h4M17 17h4" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" />
            @break
        @case('archive')
            <rect x="3" y="4" width="18" height="5" rx="1" /><path d="M5 9v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9M10 13h4" />
            @break
        @case('alert-triangle')
            <path d="M10.3 3.9 2.5 17.4A2 2 0 0 0 4.2 20h15.6a2 2 0 0 0 1.7-2.6L13.7 3.9a2 2 0 0 0-3.4 0Z" /><path d="M12 9v4M12 17h.01" />
            @break
        @case('dollar-sign')
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
            @break
        @case('arrow-up-right')
            <path d="M7 17 17 7M7 7h10v10" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
