<?php

return [
    ['group' => 'Overview', 'label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'roles' => ['admin']],
    ['group' => 'Catalog', 'label' => 'Movies', 'route' => 'admin.movies.index', 'icon' => 'clapperboard', 'roles' => ['admin']],
    ['group' => 'Catalog', 'label' => 'Genres', 'route' => 'admin.genres.index', 'icon' => 'tags', 'roles' => ['admin']],
    ['group' => 'Cinema', 'label' => 'Cinema & Screens', 'route' => 'admin.cinema.index', 'icon' => 'armchair', 'roles' => ['admin']],
    ['group' => 'Cinema', 'label' => 'Showtimes', 'route' => 'admin.showtimes.index', 'icon' => 'calendar-clock', 'roles' => ['admin']],
    ['group' => 'Sales', 'label' => 'Bookings', 'route' => 'admin.bookings.index', 'icon' => 'ticket', 'roles' => ['admin']],
    ['group' => 'Sales', 'label' => 'Payments', 'route' => 'admin.payments.index', 'icon' => 'credit-card', 'roles' => ['admin']],
    ['group' => 'Sales', 'label' => 'Refunds', 'route' => 'admin.refunds.index', 'icon' => 'undo-2', 'roles' => ['admin']],
    ['group' => 'Entry', 'label' => 'Tickets & Scans', 'route' => 'admin.tickets.index', 'icon' => 'scan-line', 'roles' => ['admin']],
    ['group' => 'People', 'label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users', 'roles' => ['admin']],
    ['group' => 'System', 'label' => 'Settings', 'route' => 'admin.settings.edit', 'icon' => 'settings', 'roles' => ['admin']],
    ['group' => 'System', 'label' => 'Stripe Events', 'route' => 'admin.stripe-events.index', 'icon' => 'webhook', 'roles' => ['admin']],
    ['group' => 'System', 'label' => 'Audit Logs', 'route' => 'admin.audit-logs.index', 'icon' => 'scroll-text', 'roles' => ['admin']],
];
